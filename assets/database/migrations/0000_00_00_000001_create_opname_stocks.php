<?php

use Hanafalah\ModuleOpnameStock\Models\OpnameStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;

return new class extends Migration
{
    use NowYouSeeMe;
    private $__table, $__table_service;

    public function __construct()
    {
        $this->__table = app(config('database.models.OpnameStock', OpnameStock::class));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $table_name = $this->__table->getTable();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $table->ulid("id")->primary()->nullable(false);
                $table->string("author_id", 36)->nullable(true);
                $table->string("author_type", 100)->nullable(true);
                $table->string("warehouse_id", 36)->nullable(false);
                $table->string("warehouse_type", 100)->nullable(false);
                $table->string("status")->nullable(false);
                $table->timestamp("reported_at")->nullable(true);
                $table->json('props')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(["author_id", "author_type"], "idx_author");
                $table->index(["warehouse_id", "warehouse_type"], "idx_warehouse");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->__table->getTable());
    }
};
