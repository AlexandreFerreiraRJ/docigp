<?php

namespace App\Data\Repositories;

use Carbon\Carbon;
use App\Data\Models\Entry;
use Illuminate\Support\Str;
use App\Data\Traits\RepositoryActionable;

class Entries extends Repository
{
    use RepositoryActionable;

    /**
     * @var string
     */
    protected $model = Entry::class;

    protected $congressmanBudgetId;

    protected $data;

    public function allFor($congressmanId, $congressmanBudgetId)
    {
        return $this->applyFilter(
            $this->newQuery()
                ->join(
                    'congressman_budgets',
                    'congressman_budgets.id',
                    'entries.congressman_budget_id'
                )
                ->join(
                    'congressman_legislatures',
                    'congressman_legislatures.id',
                    'congressman_budgets.congressman_legislature_id'
                )
                ->where(
                    'congressman_legislatures.congressman_id',
                    $congressmanId
                )
                ->where('congressman_budgets.id', $congressmanBudgetId)
        );
    }

    private function firstOrCreateProvider($to, $provider_cpf_cnpj)
    {
        return app(Providers::class)->firstOrCreate(
            ['cpf_cnpj' => $provider_cpf_cnpj],
            ['name' => $to]
        );
    }

    /**
     * @param mixed $congressmanBudgetId
     * @return \App\Data\Repositories\Entries
     */
    public function setCongressmanBudgetId($congressmanBudgetId): Entries
    {
        $this->congressmanBudgetId = $congressmanBudgetId;

        return $this;
    }

    public function transform($data)
    {
        $this->addTransformationPlugin(function ($entry) {
            $entry['date_formatted'] = Carbon::parse($entry['date'])->format(
                'd/m/Y'
            );

            $entry['date'] = $entry['date_formatted'];

            $entry['value_formatted'] = to_reais($entry['value']);

            $entry['value_abs'] = abs($entry['value']);

            $entry['cost_center_name_formatted'] = Str::limit(
                $entry['cost_center_name'],
                60,
                '...'
            );

            $entry['name'] = ($forCongressman = in_array(
                $entry['cost_center_id'],
                ['1', '2', '3']
            ))
                ? $entry['to']
                : $entry['provider_name'];

            $entry['cpf_cnpj'] = $forCongressman
                ? null
                : "{$entry['provider_type']}: {$entry['provider_cpf_cnpj']}";

            return $entry;
        });

        return parent::transform($data);
    }

    public function store()
    {
        $this->data['congressman_budget_id'] = $this->congressmanBudgetId;

        $this->data['provider_id'] = $this->firstOrCreateProvider(
            $this->data['to'],
            $this->data['provider_cpf_cnpj']
        )->id;

        $this->storeFromArray($this->data);
    }

    /**
     * @param $callable
     * @return mixed
     */
    public function withGlobalScopesDisabled($callable)
    {
        Entry::disableGlobalScopes();

        $result = $callable();

        Entry::enableGlobalScopes();

        return $result;
    }
}
