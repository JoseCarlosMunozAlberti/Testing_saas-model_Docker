<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesforceService
{
    protected bool $enabled;
    protected ?string $loginUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected string $apiVersion;

    public function __construct()
    {
        $this->enabled = (bool) config('services.salesforce.enabled', false);
        $this->loginUrl = config('services.salesforce.login_url');
        $this->clientId = config('services.salesforce.client_id');
        $this->clientSecret = config('services.salesforce.client_secret');
        $this->apiVersion = config('services.salesforce.api_version', 'v67.0');
    }

    /**
     * Autentica con Salesforce vía OAuth 2.0 Client Credentials Grant.
     * Devuelve array con access_token e instance_url, o null si falla.
     */
    public function autenticar(): ?array
    {
        if (!$this->enabled || !$this->loginUrl || !$this->clientId || !$this->clientSecret) {
            return null;
        }

        try {
            $loginUrl = rtrim($this->loginUrl, '/');
            $response = Http::asForm()->post("{$loginUrl}/services/oauth2/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $accessToken = $response->json('access_token');
                $instanceUrl = $response->json('instance_url');

                if ($accessToken && $instanceUrl) {
                    return [
                        'access_token' => $accessToken,
                        'instance_url' => $instanceUrl,
                    ];
                }
            }

            Log::error('Error de autenticación Salesforce (Client Credentials). Código: ' . $response->status());
            return null;
        } catch (\Throwable $e) {
            Log::error('Excepción de autenticación Salesforce: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sincroniza una venta pagada como Opportunity en Salesforce.
     * NUNCA debe lanzar excepción ni revertir la transacción de la venta.
     */
    public function sincronizarVenta(Venta $venta): bool
    {
        if (!$this->enabled) {
            $venta->update([
                'salesforce_sync_status' => 'deshabilitada',
            ]);
            return false;
        }

        try {
            $auth = $this->autenticar();
            if (!$auth) {
                $venta->update([
                    'salesforce_sync_status' => 'fallida',
                    'salesforce_sync_error' => 'No se pudo obtener el token de acceso a Salesforce (Client Credentials).',
                ]);
                return false;
            }

            $accessToken = $auth['access_token'];
            $instanceUrl = rtrim($auth['instance_url'], '/');
            $tenantNombre = $venta->tenant ? $venta->tenant->nombre_comercial : 'Tenant';

            $opportunityData = [
                'Name' => "Venta #{$venta->id} - {$tenantNombre}",
                'Amount' => (float) $venta->total,
                'StageName' => 'Closed Won',
                'CloseDate' => ($venta->pagada_at ?? now())->format('Y-m-d'),
            ];

            $response = Http::withToken($accessToken)
                ->post("{$instanceUrl}/services/data/{$this->apiVersion}/sobjects/Opportunity", $opportunityData);

            if ($response->successful() && $response->json('id')) {
                $sfId = $response->json('id');
                $venta->update([
                    'salesforce_id' => $sfId,
                    'salesforce_sync_status' => 'sincronizada',
                    'salesforce_sync_error' => null,
                ]);
                return true;
            }

            $statusCode = $response->status();
            Log::error("Error al crear Opportunity en Salesforce para Venta #{$venta->id}. Status: {$statusCode}");
            $venta->update([
                'salesforce_sync_status' => 'fallida',
                'salesforce_sync_error' => "Error al crear Opportunity en Salesforce (HTTP {$statusCode}).",
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("Excepción en SalesforceService para Venta #{$venta->id}: " . $e->getMessage());
            $venta->update([
                'salesforce_sync_status' => 'fallida',
                'salesforce_sync_error' => 'Excepción al conectar con Salesforce.',
            ]);
            return false;
        }
    }
}
