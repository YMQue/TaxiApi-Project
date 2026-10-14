<?php
namespace App\Repositories\Contracts\Admin;

interface SettingRepositoryInterface
{
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function update(array $values): array;
}