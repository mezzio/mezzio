# Emitters

To simplify the usage of Mezzio, we added the `run()` method, which handles
the incoming request, and emits a response.

The latter aspect, emitting the response, is the responsibility of an
[emitter](https://docs.laminas.dev/laminas-httphandlerrunner/emitters/).
An emitter accepts a response instance, and then does something with it, usually
sending the response back to a browser.

The laminas/laminas-httphandlerrunner package defines an `EmitterInterface`,
and three emitter implementations. Two of these,
`Laminas\HttpHandlerRunner\Emitter\SapiEmitter` and
`Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter`, send headers and output
using PHP's standard SAPI mechanisms (the `header()` method and the output
buffer).

We recognize that there are times when you may want to use alternate emitter
implementations; for example, if you use [React](http://reactphp.org), the SAPI
emitter will likely not work for you.

To facilitate alternate emitters, we offer two facilities:

- First, a `Laminas\HttpHandlerRunner\RequestHandlerRunner` instance is composed
  in the `Application` instance, and you can specify an alternate
  emitter during instantiation, or via the `Laminas\HttpHandlerRunner\Emitter\EmitterInterface`
  service when using the container factory.
- Second, we provide `Laminas\HttpHandlerRunner\Emitter\EmitterStack`, which allows
  you to compose multiple emitter strategies; the first to return a boolean true
  will cause execution of the stack to short-circuit.  The `RequestHandlerRunner`
  service composes an `EmitterStack` by default, with an `SapiEmitter` composed
  at the bottom of the stack.
