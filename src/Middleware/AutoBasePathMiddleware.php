<?php

namespace Contributte\Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Drop base path from URL by auto-detection
 *
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class AutoBasePathMiddleware extends BaseMiddleware
{

	// Attributes in ServerRequestInterface
	const ATTR_ORIG_PATH = 'C-Orig-Path';
	const ATTR_BASE_PATH = 'C-Base-Path';
	const ATTR_PATH = 'C-Path';

	/**
	 * @param ServerRequestInterface $psr7Request
	 * @param ResponseInterface $psr7Response
	 * @param callable $next
	 * @return ResponseInterface
	 */
	public function __invoke(ServerRequestInterface $psr7Request, ResponseInterface $psr7Response, callable $next)
	{
		$uri = $psr7Request->getUri();
		$basePath = $uri->getPath();

		// Base-path auto detection (inspired in @nette/routing)
		$lpath = strtolower($uri->getPath());
		$script = isset($_SERVER['SCRIPT_NAME']) ? strtolower($_SERVER['SCRIPT_NAME']) : '';
		if ($lpath !== $script) {
			$max = min(strlen($lpath), strlen($script));
			for ($i = 0; $i < $max && $lpath[$i] === $script[$i]; $i++) {
				// Find basePath replacing parsing scriptPath
				$basePath = $i ? substr($basePath, 0, strrpos($basePath, '/', $i - strlen($basePath) - 1) + 1) : '/';
			}
		}

		// Try replace path or just use slash (/)
		$pos = strrpos($basePath, '/');
		if ($pos !== FALSE) {
			// Cut base path by last slash (/)
			$basePath = substr($basePath, 0, $pos + 1);
			// Drop part of path (basePath)
			$newPath = str_replace($basePath, NULL, $uri->getPath());
		} else {
			$newPath = '/';
		}

		// New path always starts with slash (/)
		$newPath = '/' . ltrim($newPath, '/');

		// Update request with new path (fake path) and also provide new attributes
		$psr7Request = $psr7Request
			->withAttribute(self::ATTR_ORIG_PATH, $uri->getPath())
			->withAttribute(self::ATTR_BASE_PATH, $basePath)
			->withAttribute(self::ATTR_PATH, $newPath)
			->withUri($uri->withPath($newPath));

		// Pass to next middleware
		return $next($psr7Request, $psr7Response);
	}

}