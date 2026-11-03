<?php

namespace Modules\CustomerSystems\Services;

class ApiException extends \RuntimeException
{
    public $httpStatus;
    public $curlErrno;
    public $phase;

    public function __construct($reason, $httpStatus, $curlErrno, $phase = 'api')
    {
        parent::__construct($reason);
        $this->httpStatus = (int) $httpStatus;
        $this->curlErrno = (int) $curlErrno;
        $this->phase = $phase;
    }
}
