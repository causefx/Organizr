#!/usr/bin/env bash
if [ "${HTTPD_SSL:-SSL}" == "SSL" ]; then
  PROTO="https"
else
  PROTO="http"
fi

curl --head --insecure --silent --show-error --fail "${PROTO}://localhost:${HTTPD_PORT}/"
