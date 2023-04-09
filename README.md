**Emdrive**

This bundle provides a built-in service that runs indefinitely and allows to trigger execution of Symfony\Component\Console\Command\Command on daily or interval bases 

**Installation**

Open a command console, enter your project directory and execute the following command to download the latest version of this bundle:

`composer require maksslesarenko/emdrive`

**Configuration**
```
emdrive:
    server_name: main # incase app is running on multiple servers this has to be unique
    pool_size: 5 # number of simultanious commands allowed to be execute
    tick_interval: 2000000 # interval in microseconds between service loop checks
    cmd_start: bin/console %s -vv -e prod > /dev/null 2>&1 & # template for scheduled commands to be executed with
    cmd_kill: kill -2 %s > /dev/null 2>&1 & # template for scheduled commands to be stopped with

    storage:
        dsn: '%env(DATABASE_URL)%' # dsn for mysql or sqlite database to store schedule

    lock: emdrive_lock # lock name that is configured in framework section
    pid_dir: var/pid # directory to store process ids for executed commands
    log_dir: var/elog # directory to store logs
    lock_dir: var/lock # directory to store lock files

framework:
    lock:
        enabled: true
        resources:
            emdrive_lock: flock
            #emdrive_lock: [flock, 'memcached://localhost'] # incase multiple servers are used to run app
```

**Commands**

`bin/console emdrive:deploy` # main deploy command that detects other deploy commands and executes them in order to deploy bundle 

`bin/console emdrive:deploy:update-schedule` # deploy command to update schedule

`bin/console emdrive:configure-schedule [<name>]` # change command schedule time/interval

`bin/console emdrive:service:run [--watch]` # launch service

`bin/console emdrive:service:stop [--stop-all]` # stop service

`bin/console emdrive:service:status [--watch]` # check if service is running

`bin/console emdrive:dump-schedule` # dump schedule to console

Example:
```
+----+--------------------------+-------------+---------+---------------------+---------------------+---------------+----------------+
| Id | Name                     | Server name | Status  | Last start at       | Next start at       | Schedule type | Schedule value |
+----+--------------------------+-------------+---------+---------------------+---------------------+---------------+----------------+
| 1  | first-scheduled-command  | main        | stopped | 2018-04-06 23:19:01 | 2018-04-06 23:20:01 | interval      | 1 minutes      |
| 2  | second-scheduled-command | main        | stopped | 2018-04-06 23:05:00 | 2018-04-07 23:05:00 | time          | 23:05          |
+----+--------------------------+-------------+---------+---------------------+---------------------+---------------+----------------+


```

**Create scheduled command**

```
use Emdrive\Command\ScheduledCommandInterface;
use Emdrive\InterruptableExecutionTrait;
use Emdrive\LockableExecutionTrait;
use Symfony\Component\Console\Command\Command;

class FisrtScheduledCommand extends Command implements ScheduledCommandInterface
{
    use LockableExecutionTrait;
    use InterruptableExecutionTrait;

    public function configure()
    {
        $this->setName('first-scheduled-command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        ...
        foreach ($collection as $item) {
            if ($this->isInterrupted()) {
                break;
            }
            ...
        }
    }
}
```

After you have created new scheduled command run deploy: 

`bin/console emdrive:deploy`

then configure schedule:

`bin/console emdrive:configure-schedule first-scheduled-command`

**Supervisord configuration**

```
[program:emdrive]
command=/code/bin/console emdrive:service:run
stderr_logfile = /var/log/supervisor/emdrive.error.log
stdout_logfile = /var/log/supervisor/emdrive.log
stopsignal = INT
```

**Licence**

This bundle is released under the MIT license.