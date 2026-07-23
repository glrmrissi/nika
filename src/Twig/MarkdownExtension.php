<?php

namespace App\Twig;

use League\CommonMark\ConverterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConverterInterface $markdownConverter,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', $this->markdown(...), ['is_safe' => ['html']]),
        ];
    }

    public function markdown(string $content): string
    {
        $html = $this->markdownConverter->convert($content)->getContent();

        return preg_replace('/<img\b[^>]*>/i', '', $html);
    }
}
