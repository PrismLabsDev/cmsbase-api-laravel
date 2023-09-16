<?php

namespace App\Http\Controllers\tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

use App\Models\tenant\Collection;
use App\Models\tenant\CollectionFieldType;
use App\Models\tenant\CollectionField;
use App\Models\tenant\User;

class DocumentController extends Controller
{

  public function index (Request $request, Collection $collection){
    try {

      $tableName = "collection-{$collection->name}";

      $query = DB::table($tableName);

      $documents = $query->get();

      return response([
        'message' => 'All collections documents.',
        'documents' => $documents
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function show (Request $request, Collection $collection){
    try {

      $collection->load(['fields', 'fields.type']);

      return response([
        'message' => 'Collections.',
        'collection' => $collection
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function store (Request $request){
    $request->validate([
			'name' => ['required'],
      'public' => ['required'],
		]);

    try {
      $newCollection = Collection::create([
        'name' => $request->name,
        'public' => $request->public,
      ]);

      $tableName = "collection-{$newCollection->name}";

      Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->boolean('published')->default(false);
        $table->timestamps();
      });

      return response([
        'message' => 'New collection created.',
        'collection' => $newCollection
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function addField (Request $request, Collection $collection){
    $request->validate([
			'name' => ['required'],
      'type_id' => ['required'],
		]);

    try {

      $collectionFieldType = CollectionFieldType::where('id', $request->type_id)->first();

      $newCollectionField = CollectionField::create([
        'name' => $request->name,
        'collection_id' => $collection->id,
        'type_id' => $collectionFieldType->id
      ]);

      $newCollectionField->load(['type']);

      $tableName = "collection-{$collection->name}";
      Schema::table($tableName, function (Blueprint $table) use ($newCollectionField) {
        switch ($newCollectionField->type->datatype){
          case "tinyInteger":
            $table->tinyInteger($newCollectionField->name)->nullable();
            break;
          case "unsignedTinyInteger":
            $table->unsignedTinyInteger($newCollectionField->name)->nullable();
            break;
          case "smallInteger":
            $table->smallInteger($newCollectionField->name)->nullable();
            break;
          case "unsignedSmallInteger":
            $table->unsignedSmallInteger($newCollectionField->name)->nullable();
            break;
          case "integer":
            $table->integer($newCollectionField->name)->nullable();
            break;
          case "unsignedInteger":
            $table->unsignedInteger($newCollectionField->name)->nullable();
            break;
          case "mediumInteger":
            $table->mediumInteger($newCollectionField->name)->nullable();
            break;
          case "unsignedMediumInteger":
            $table->unsignedMediumInteger($newCollectionField->name)->nullable();
            break;
          case "bigInteger":
            $table->bigInteger($newCollectionField->name)->nullable();
            break;
          case "unsignedBigInteger":
            $table->unsignedBigInteger($newCollectionField->name)->nullable();
            break;
          case "decimal":
            $table->decimal($newCollectionField->name)->nullable();
            break;
          case "unsignedDecimal":
            $table->unsignedDecimal($newCollectionField->name)->nullable();
            break;
          case "float":
            $table->float($newCollectionField->name)->nullable();
            break;
          case "double":
            $table->double($newCollectionField->name)->nullable();
            break;
          case "char":
            $table->char($newCollectionField->name)->nullable();
            break;
          case "string":
            $table->string($newCollectionField->name)->nullable();
            break;
          case "tinyText":
            $table->tinyText($newCollectionField->name)->nullable();
            break;
          case "text":
            $table->text($newCollectionField->name)->nullable();
            break;
          case "mediumText":
            $table->mediumText($newCollectionField->name)->nullable();
            break;
          case "longText":
            $table->longText($newCollectionField->name)->nullable();
            break;
          case "boolean":
            $table->boolean($newCollectionField->name)->nullable();
            break;
          case "date":
            $table->date($newCollectionField->name)->nullable();
            break;
          case "time":
            $table->time($newCollectionField->name)->nullable();
            break;
          case "dateTime":
            $table->dateTime($newCollectionField->name)->nullable();
            break;
          case "timestamp":
            $table->timestamp($newCollectionField->name)->nullable();
            break;
        }
      });

      return response([
        'message' => 'Collection field added.',
        'field' => $newCollectionField->load(['type'])
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function removeField (Request $request, Collection $collection, $fieldName){
    try {

      $collectionFieldTypeRemoved = CollectionField::where('collection_id', $collection->id)->where('name', $fieldName)->delete();

      $tableName = "collection-{$collection->name}";
      Schema::table($tableName, function (Blueprint $table) use ($fieldName) {
        $table->dropColumn($fieldName);
      });

      return response([
        'message' => 'Collection field removed.',
        '$collectionFieldTypeRemoved' => $collectionFieldTypeRemoved
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function destroy (Request $request, Collection $collection){
    try {

      $tableName = "collection-{$collection->name}";
      Schema::dropIfExists($tableName);

      $collection->delete();

      return response([
        'message' => 'Collection removed.'
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }




  // Field types
  // TODO: Move to own controller
  public function getFieldTypes(Request $request){
    try {

      $query = CollectionFieldType::query();
      $types = $query->get();

      return response([
        'message' => 'List all collection field types.',
        'types' => $types
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }

  public function createFieldType(Request $request){

    $request->validate([
			'name' => ['required'],
      'datatype' => ['required'],
		]);

    try {
      $newCollectionFieldType = CollectionFieldType::create([
        'name' => $request->name,
        'datatype' => $request->datatype,
      ]);

      return response([
        'message' => 'New collection field type created.',
        'type' => $newCollectionFieldType
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }
}