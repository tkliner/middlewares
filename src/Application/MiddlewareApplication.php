<?php

namespace Contributte\Middlewares\Application;

use Contributte\Psr7\Psr7ResponseFactory;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class MiddlewareApplication extends AbstractApplication
{

	/**
	 * @return ServerRequestInterface
	 */
	protected function createInitialRequest()
	{
		return Psr7ServerRequestFactory::fromGlobal();
	}

	/**
	 * @return ResponseInterface
	 */
	protected function createInitialResponse()
	{
		return Psr7ResponseFactory::fromGlobal();
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function finalize(ServerRequestInterface $request, ResponseInterface $response)
	{
		$this->sendStatus($response);
		$this->sendHeaders($response);
		$this->sendBody($response);

		return $response;
	}

	/**
	 * @param ResponseInterface $response
	 * @return void
	 */
	protected function sendStatus(ResponseInterface $response)
	{
		$version = $response->getProtocolVersion();
		$status = $response->getStatusCode();
		$phrase = $response->getReasonPhrase();
		header(sprintf('HTTP/%s %s %s', $version, $status, $phrase));
	}

	/**
	 * @param ResponseInterface $response
	 * @return void
	 */
	protected function sendHeaders(ResponseInterface $response)
	{
		foreach ($response->getHeaders() as $name => $values) {
			$this->sendHeader($name, $values);
		}
	}

	/**
	 * @param string $name
	 * @param array $values
	 * @return void
	 */
	protected function sendHeader($name, $values)
	{
		$name = str_replace('-', ' ', $name);
		$name = ucwords($name);
		$name = str_replace(' ', '-', $name);
		foreach ($values as $value) {
			header(sprintf('%s: %s', $name, $value), FALSE);
		}
	}

	/**
	 * @param ResponseInterface $response
	 * @return void
	 */
	protected function sendBody(ResponseInterface $response)
	{
		$stream = $response->getBody();
		$stream->rewind();
		while (!$stream->eof()) {
			echo $stream->read(8192);
		}
	}

}
