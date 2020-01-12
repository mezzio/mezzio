# Should You Choose mezzio Over laminas-mvc?

We recommend that you choose Mezzio for any new project &mdash; _if the
choice is yours to make_.

## Why Use laminas-mvc?

laminas-mvc is a proven platform, with half a decade of development behind it. It
is stable and battle-tested in production platforms.

Because it is opinionated about project structure and architecture, fewer
decisions need be made up front; developers know where new code goes, and how it
will wire into the overall application.

Additionally, a number of training courses exist, including [offerings by
Zend](https://www.zend.com/training/zf-fundamentals), allowing you
or your team to fully learn the framework and take advantage of all its features.

Finally, laminas-mvc has a lively [module ecosystem](https://packagist.org/search/?q=laminas),
allowing you to add features and capabilities to your application without
needing to develop them from scratch.

## We Recommend Mezzio

[laminas-mvc](https://github.com/laminas/laminas-mvc) has many preconceptions
about how things work, yet they're very broad and general. What’s more, it
also has several pre-wired structures in place that may either aid you &mdash;
or get in your way.

As a result, you are required to know a lot of what those things are &mdash; *if* you
want to use it optimally. And to acquire that depth of knowledge, you’re going
to need to spend a lot of time digging deep into laminas-mvc’s internals before
you begin to get the most out of it.

To quote Laminas project lead, [Matthew Weier O’Phinney](https://mwop.net):

> The problem is that laminas-mvc is anything but beginner-friendly at this point.
> You're required to deep dive into the event manager, service manager, and
> module system &mdash; right from the outset; And to do this you need more than a
> passing understanding of object-oriented programming and a range of design
> patterns.

Mezzio (specifically applications based on
[the Mezzio Skeleton Installer](https://docs.mezzio.dev/mezzio/getting-started/skeleton/))
on the other hand, comes with barely any of these assumptions and requirements.

It provides a very minimalist structure. Essentially all you have to become
familiar with are five core components. These are:

- A DI container.
- A router.
- An error handler for development.
- A template engine (if you’re not creating an API).
- PSR-7 messages and http-interop (future PSR-15) middleware.

In many cases, these are provided for you by the skeleton, and do not require
any additional knowledge on your part. Given that, you can quickly get up to
speed with the framework and begin creating the application that you need. We
believe that this approach &mdash; in contrast to the laminas-mvc approach &mdash;
is more flexible and accommodating.

What’s more, you can mix and match the types of applications that you create.

- Do you just need an API? Great; you can do that quite quickly.
- Do you want an HTML-based front-end? That’s available too.

When building applications with Mezzio, you can make use of the various Laminas
components, or any third-party components or middleware. You can pick and
choose what you need, as and when you need it. You’re not bound by many, if
any, constraints and design decisions.

## In Conclusion

For what it’s worth, we’re **not** saying that laminas-mvc is a poor choice!  What
we are saying is:

1. The learning curve, from getting started to building the first application,
   is _significantly_ lower with Mezzio
2. The ways in which you can create applications, whether through multiple
   pieces of middleware or by combining multiple Mezzio apps, into one
   larger one, is a much more efficient and fluid way to work

Ultimately, the choice is always up to you, your team, and your project’s needs.
We just want to ensure that you’ve got all the information you need, to make an
informed decision.
