<?php

namespace Contributte\Middlewares\Utils;

use Contributte\Middlewares\Exception\InvalidStateException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class ChainBuilder
{

	/** @var array */
	private $middlewares = [];

	/**
	 * @param mixed $middleware
	 * @return void
	 */
	public function add($middleware)
	{
		if (!is_callable($middleware)) {
			throw new InvalidStateException('Middleware is not callable');
		}

		$this->middlewares[] = $middleware;
	}

	/**
	 * @param array $middlewares
	 * @return void
	 */
	public function addAll(array $middlewares)
	{
		foreach ($middlewares as $middleware) {
			$this->add($middleware);
		}
	}

	/**
	 * @return callable
	 */
	public function create()
	{
		if (!$this->middlewares) {
			throw new InvalidStateException('At least one middleware is needed');
		}

		$next = Lambda::leaf();

		$middlewares = $this->middlewares;
		while ($middleware = array_pop($middlewares)) {
			$next = function (RequestInterface $request, ResponseInterface $response) use ($middleware, $next) {
				// Middleware should return ALWAYS response!
				return $middleware($request, $response, $next);
			};
		}

		return $next;
	}

	/**
	 * @param array $middlewares
	 * @return callable
	 */
	public static function factory(array $middlewares)
	{
		$chain = new ChainBuilder();
		$chain->addAll($middlewares);

		return $chain->create();
	}

}
