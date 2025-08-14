<?php declare(strict_types = 1);

namespace Shredio\Commentator;

final class InfoToCapture
{

	public ?int $inputTokens = null;

	public ?int $outputTokens = null;

	public ?int $reasoningTokens = null;

	public ?int $cachedTokens = null;

	public function toPriceOutput(float $inputPrice, float $cachedInputPrice, float $outputPrice, string $currency): string
	{
		$cachedTokens = $this->cachedTokens ?? 0;
		$inputTokens = ($this->inputTokens ?? 0) - $cachedTokens;
		$outputTokens = $cachedTokens;

		$inputPriceResult = $inputTokens / 1_000 * $inputPrice;
		$cachedInputPriceResult = $cachedTokens / 1_000 * $cachedInputPrice;
		$outputPriceResult = $outputTokens / 1_000 * $outputPrice;
		$totalPriceResult = $inputPriceResult + $cachedInputPriceResult + $outputPriceResult;

		$str = sprintf("Input price: %s %s (1000x)\n", number_format($inputPriceResult, 4), $currency);
		$str .= sprintf("Cached input price: %s %s (1000x)\n", number_format($cachedInputPriceResult, 4), $currency);
		$str .= sprintf("Output price: %s %s (1000x)\n", number_format($outputPriceResult, 4), $currency);
		$str .= sprintf("Total price: %s %s (1000x)\n", number_format($totalPriceResult, 4), $currency);

		return $str;
	}

}
