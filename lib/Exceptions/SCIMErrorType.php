<?php

namespace OCA\SCIMServiceProvider\Exception;

class SCIMErrorType
{
    const INVALID_FILTER = "invalidFilter";
    const TOO_MANY = "tooMany";
    const UNIQUENESS = "uniqueness";
    const MUTABILITY = "mutability";
    const INVALID_SYNTAX = "invalidSyntax";
    const INVALID_PATH = "invalidPath";
    const NO_TARGET = "noTarget";
    const INVALID_VALUE = "invalidValue";
    const INVALID_VERS = "invalidVers";
    const SENSITIVE = "sensitive";
}
