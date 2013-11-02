# Flow
The *flow* function (*scheduler*) takes a list of *co-routines* and executes them all in cooperative manner.
It returns when all the *co-routines* have finished.

API:
```
flow(Generator[]) → value[]
```


There are several scheduler implementations (types of *flow* function):
 - naive - no cooperation, reduced co-routines to normal synchronous functions
 - horizontal - in rounds, one step of each co-routines at a time
 - parallel - fully parallel, co-routines are rescheduled immediately when possible


## Naive flow
There is no cooperation.
It takes one co-routine after other, running each until finished.

This is equivalent to synchronous code.
*yield* simply blocks until the promise gets resolved.

Implemented by `Flow\Schedulers\NaiveScheduler`.


## Horizontal flow
Cooperation in rounds.
In the first round, all co-routines are started and run until they yield.
Then we wait until all promises are resolved and another round starts.

Implemented by `Flow\Schedulers\HorizontalScheduler`.


## Parallel flow
Fully parallel cooperation.
All co-routines are started and run until they yield.
Then, once a promise gets resolved, its corresponding co-routine gets resumed.

Not implemented yet.


# Co-routines
*Co-routine* is a special form of *generator*.
It's speciality comes only by restrictions posed on this generator.
From outside view, it is not possible to tell a *co-routine* from a *generator*.

A *co-routine* can *yield* four kinds of values:
 - *promise* - `instanceof PromiseInterface`
 - *co-routine* - `instanceof Generator`
 - *result* - `instanceof Flow\Result`
 - *pure-value* - anything else


`yield promise` will pause the *co-routine* until the *promise* gets resolved.
When the *promise* gets resolved, the scheduler will resume the *co-routine* again:
 - if the promise was *fulfilled*, yield will give the fullfillment value;
 - if the promise was *rejected*, yield will throw the rejection value as exception


`yield co-routine` will schedule another co-routine and adds it to cooperative set.
Once the inner co-routine gets finished, the original co-routine is resumed
and yield gives the value of inner co-routine.
It is very similar to previous case.
Example:
```
$data = (yield $client->fetchValueUsingCoroutine());
```


`yield result` will completely stop the *co-routine*.
Scheduler will take the value of *result* and return it, when all other *co-routines* are finished.
The result instance is normally created and yielded by calling
`yield result($value);`


`yield pure-value` will not pause the *co-routine*, immediately giving the pure value as result of yield.
Thus it acts as *no-op*.
It is here for convenience, so that model layer can return either *promise* (when it needs to wait for the result)
or *pure value* (when it known the result immediately without blocking).


Effectively, you can use yield in two ways:
 - `$foo = yield $client->request(...);` to read data; it will return a pure-value or throw
 - `yield result($foo);` to provide return value; it acts like *return* in normal function



# Promises
A *promise* is wrapper for a *value*, which may not be available yet.

When *scheduler* executes a *co-routine* and it yields a promise,
scheduler will wait for the promise to gets resolved.

If the *promise* gets fulfilled and provides a *pure value*, scheduler passes this value back to *co-routine*.
When the *promise* gets rejected, scheduler throws into the *co-routine*.
When the *promise* returns another promise, scheduler waits that one to finish.
