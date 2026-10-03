<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentStructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shared list/create/edit/delete screens for simple catalog records
 * (sources, categories, subjects, chapters, topics, important links).
 * Each subclass describes its fields and columns; validation stays in a
 * dedicated Form Request per subclass and writes go through
 * ContentStructureService (audited, safe deletes).
 */
abstract class CatalogController extends Controller
{
    public function __construct(protected readonly ContentStructureService $structure) {}

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /** Route name prefix, for example "admin.sources". */
    abstract protected function routeBase(): string;

    abstract protected function pluralTitle(): string;

    abstract protected function singularTitle(): string;

    /**
     * Form fields: name, label, type (text|textarea|number|url|select|checkbox), options, hint, required.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function fields(): array;

    /**
     * Table columns: label => fn (Model $record): string.
     *
     * @return array<string, callable(Model): string>
     */
    abstract protected function columns(): array;

    /**
     * @return Builder<Model>
     */
    protected function query(Request $request): Builder
    {
        return ($this->modelClass())::query()->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Optional list filters: name, label, options.
     *
     * @return list<array<string, mixed>>
     */
    protected function filters(): array
    {
        return [];
    }

    protected function description(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(Request $request): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        return view('admin.catalog.index', [
            'records' => $this->query($request)->paginate(25)->withQueryString(),
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'routeBase' => $this->routeBase(),
            'title' => $this->pluralTitle(),
            'singular' => $this->singularTitle(),
            'description' => $this->description(),
        ]);
    }

    public function create(Request $request): View
    {
        $class = $this->modelClass();

        return $this->form(new $class($this->defaults($request)));
    }

    public function edit(Request $request, int $id): View
    {
        return $this->form($this->find($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->structure->delete($this->find($id));

        return to_route("{$this->routeBase()}.index")->with('success', 'تم الحذف.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function storeRecord(array $data): RedirectResponse
    {
        $this->structure->create($this->modelClass(), $data);

        return to_route("{$this->routeBase()}.index")->with('success', 'تمت الإضافة.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function updateRecord(int $id, array $data): RedirectResponse
    {
        $this->structure->update($this->find($id), $data);

        return to_route("{$this->routeBase()}.index")->with('success', 'تم حفظ التعديلات.');
    }

    protected function find(int $id): Model
    {
        return ($this->modelClass())::query()->findOrFail($id);
    }

    private function form(Model $record): View
    {
        return view('admin.catalog.form', [
            'record' => $record,
            'fields' => $this->fields(),
            'routeBase' => $this->routeBase(),
            'title' => ($record->exists ? 'تعديل ' : 'إضافة ').$this->singularTitle(),
        ]);
    }
}
