<?php

namespace App\Twig;

use League\CommonMark\ConverterInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private readonly HtmlSanitizer $htmlSanitizer;

    public function __construct(
        private readonly ConverterInterface $markdownConverter,
    ) {
        $this->htmlSanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowElement('a', ['href', 'title'])
                ->allowElement('strong')
                ->allowElement('em')
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('h1')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('h4')
                ->allowElement('h5')
                ->allowElement('h6')
                ->allowElement('blockquote')
                ->allowElement('code')
                ->allowElement('pre')
                ->allowElement('hr')
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->forceHttpsUrls()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
        );
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', $this->markdown(...), ['is_safe' => ['html']]),
        ];
    }

    public function markdown(string $content): string
    {
        $html = $this->markdownConverter->convert($content)->getContent();

        return $this->htmlSanitizer->sanitize($html);
    }
}
