<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

abstract class Filter
{
    /**
     * @var Builder
     */
    public Builder $builder;

    /**
     * @var array
     */
    protected array $filters = [];

    protected array $fields = ['*'];

    private array $manualFilters = [];

    /**
     * @var Request
     */
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    abstract public function default();

    public function add(array $filters)
    {
        $this->manualFilters = $filters;
    }

    /**
     * @param Builder $builder
     */
    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        $this->builder->select($this->fields);

        $this->default();

        $this->getFilters()->each(function ($filter, $value) {
            $this->$filter($value);
        });
    }

    /**
     * @return Collection
     */
    protected function getFilters(): Collection
    {
        return collect($this->request->only($this->filters))->merge($this->manualFilters)->flip();
    }
}
