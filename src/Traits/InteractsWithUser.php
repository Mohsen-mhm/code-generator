<?php

namespace MohsenMhm\CodeGenerator\Traits;

trait InteractsWithUser
{
    protected function info($message)
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }

    protected function error($message)
    {
        if ($this->command) {
            $this->command->error($message);
        }
    }

    protected function warn($message)
    {
        if ($this->command) {
            $this->command->warn($message);
        }
    }

    protected function ask($question, $default = null)
    {
        if ($this->command) {
            return $this->command->ask($question, $default);
        }
        
        return $default;
    }

    protected function confirm($question, $default = false)
    {
        if ($this->command) {
            return $this->command->confirm($question, $default);
        }
        
        return $default;
    }

    protected function choice($question, array $choices, $default = null)
    {
        if ($this->command) {
            return $this->command->choice($question, $choices, $default);
        }
        
        return $default;
    }
} 