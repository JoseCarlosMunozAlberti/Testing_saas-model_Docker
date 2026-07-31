#!/bin/sh
echo "Inicializando LocalStack para PS Tenant..."
awslocal s3 mb s3://saas-comprobantes || true
echo "Bucket saas-comprobantes listo en LocalStack."
