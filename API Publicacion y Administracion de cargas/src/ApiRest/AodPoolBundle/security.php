<?php
use Swagger\Annotations as SWG;
/**
 * @SWG\SecurityScheme(
 *   securityDefinition="api_key",
 *   type="apiKey",
 *   in="header",
 *   name="api_key"
 * )
 */
/**
 * @SWG\SecurityScheme(
 *   securityDefinition="aodPoolstore_auth",
 *   type="apiKey",
 *   in="header",
 *   name="api_key",
 *   scopes={
 *     "read:publicaciones": "read your pets",
 *     "write:publicaciones": "modify pets in your account"
 *   }
 * )
 */

