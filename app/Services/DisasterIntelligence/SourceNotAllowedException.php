<?php

namespace App\Services\DisasterIntelligence;

use RuntimeException;

/**
 * 取得先ポリシーに違反したURLを表す例外。
 */
class SourceNotAllowedException extends RuntimeException
{
}
