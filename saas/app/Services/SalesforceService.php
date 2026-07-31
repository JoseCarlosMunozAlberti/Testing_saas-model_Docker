<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesforceService
{
    protected bool $enabled;
    protected string $loginUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $password;
    protected string $apiVersion;

    public function __construct()
    {
        $this->enabled = (bool) config('services.salesforce.enabled', env('SF_ENABLED', false));
        $this->loginUrl = config('services.salesforce.login_url', env('SF_LOGIN_URL', 'https://login.salesforce.com'));
        $this->clientId = config('services.salesforce.client_id', env('SF_CLIENT_ID', ''));
        $this->clientSecret = config('services.salesforce.client_secret', env('SF_CLIENT_SECRET', ''));
        $this->username = config('services.salesforce.username', env('SF_USERNAME', ''));
        $this->password = config('services.salesforce.password', env('SF_PASSWORD', ''));
        $this->apiVersion = config('services.salesforce.api_version', env('SF_API_VERSION', 'v58.0'));
    }

    /**
     * Autentica con Salesforce vía OAuth2 Password Grant.
     * Devuelve array con access_token e instance_url, o null si falla.
     */
    public function autenticar(): ?array
    {
        if (!$this->enabled || !$this->clientId || !$this->clientSecret) {
            return null;
        }

        try {
            $response = Http::asForm()->post("{$this->loginUrl}/services/oauth2/token", [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                return [
                    'access_token' => $response->json('access_token'),
                    'instance_url' => $response->json('instance_url'),
                ];
            }

            Log::error('Error de autenticación Salesforce: ' . $response->body());
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
                'salesforce_sync_status' => 'disabled',
            ]);
            return false;
        }

        try {
            $auth = $this->autenticar();
            if (!$auth) {
                $venta->update([
                    'salesforce_sync_status' => 'failed',
                    'salesforce_sync_error' => 'No se pudo obtener el token de acceso a Salesforce.',
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
                ->post("{$instanceUrl}/services/data/{$this->apiVersion}/sobjects/Opportunity/", $opportunityData);

            if ($response->successful() && $response->json('id')) {
                $sfId = $response->json('id');
                $venta->update([
                    'salesforce_id' => $sfId,
                    'salesforce_sync_status' => 'synced',
                    'salesforce_sync_error' => null,
                ]);
                return true;
            }

            $errorMsg = $response->body();
            Log::error("Error al crear Opportunity en Salesforce para Venta #{$venta->id}: {$errorMsg}");
            $venta->update([
                'salesforce_sync_status' => 'failed',
                'salesforce_sync_error' => substr($errorMsg, 0, 255),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("Excepción en SalesforceService para Venta #{$venta->id}: " . $e->getMessage());
            $venta->update([
                'salesforce_sync_status' => 'failed',
                'salesforce_sync_error' => substr($e->getMessage(), 0, 255),
            ]);
            return false;
        }
    }
}
