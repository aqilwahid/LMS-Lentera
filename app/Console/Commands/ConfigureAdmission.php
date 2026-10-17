<?php

namespace App\Console\Commands;

use App\Admission\FileAdmissionGate;
use Illuminate\Console\Command;

class ConfigureAdmission extends Command
{
    protected $signature = 'lentera:admission {--enable : Enable using configured limits} {--disable : Disable without deleting live state} {--status : Show applied limits only}';

    protected $description = 'Publish lightweight admission settings before Laravel boots';

    public function handle(): int
    {
        $path = storage_path('framework/admission-config.php');
        if ($this->option('status')) {
            $this->line(json_encode(is_file($path) ? require $path : ['enabled' => false, 'published' => false], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }
        if ($this->option('enable') && $this->option('disable')) {
            $this->error('Choose either --enable or --disable.');

            return self::FAILURE;
        }
        $config = config('admission');
        if ($this->option('enable') || $this->option('disable')) {
            $config['enabled'] = (bool) $this->option('enable');
        }
        new FileAdmissionGate(storage_path('framework/admission'), $config);
        $content = '<?php return '.var_export($config, true).';'.PHP_EOL;
        if (file_put_contents($path.'.tmp', $content) !== strlen($content) || ! rename($path.'.tmp', $path)) {
            $this->error('Cannot publish admission configuration.');

            return self::FAILURE;
        }
        $this->info($config['enabled'] ? 'Admission enabled. Verify limits with a staging load test.' : 'Admission disabled. Existing state was preserved.');

        return self::SUCCESS;
    }
}
