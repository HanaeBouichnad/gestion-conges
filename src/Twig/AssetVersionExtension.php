<?php

namespace App\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetVersionExtension extends AbstractExtension
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_version', $this->assetVersion(...)),
        ];
    }

    public function assetVersion(string $publicPath): ?int
    {
        $path = $this->kernel->getProjectDir().'/public/'.ltrim($publicPath, '/');

        return is_file($path) ? filemtime($path) : null;
    }
}
