<?php
namespace Simplex\Core;

class ResponseStatus
{
    public const OK                     = '200 OK';
    public const MOVED_PERMANENTLY      = '301 Moved Permanently';
    public const FOUND                  = '302 Found';
    public const NOT_MODIFIED           = '304 Not Modified';
    public const BAD_REQUEST            = '400 Bad Request';
    public const UNAUTHORIZED           = '401 Unauthorized';
    public const FORBIDDEN              = '403 Forbidden';
    public const NOT_FOUND              = '404 Not Found';
    public const METHOD_NOT_ALLOWED     = '405 Method Not Allowed';
    public const INTERNAL_SERVER_ERROR  = '500 Internal Server Error';
    public const NOT_IMPLEMENTED        = '501 Not Implemented';
    public const BAD_GATEWAY            = '502 Bad Gateway';
    public const SERVICE_UNAVAILABLE    = '503 Service Unavailable';
}