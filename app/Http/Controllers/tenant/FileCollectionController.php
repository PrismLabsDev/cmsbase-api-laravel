<?php

namespace App\Http\Controllers\tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

use App\Traits\RequestHelperTrait;
use App\Models\tenant\File;
use App\Models\tenant\FileCollection;

class FileCollectionController extends Controller
{

  use RequestHelperTrait;

  public function index(Request $request)
  {
    try {

      $collections = FileCollection::all();

      return response([
        'message' => 'List of file collections.',
        'collections' => $collections
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function show(Request $request, FileCollection $collection)
  {
    try {

      $collection = $collection->load(['files']);

      return response([
        'message' => 'List files in collection.',
        'collection' => $collection
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function create(Request $request)
  {
    try {

      $newCollection = FileCollection::create([
        'name' => $request->name
      ]);

      return response([
        'message' => 'New collection created.',
        'collection' => $newCollection
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function update(Request $request, FileCollection $collection)
  {
    try {
      return response([
        'message' => 'Collection updated.',
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function destroy(Request $request, FileCollection $collection)
  {
    try {
      return response([
        'message' => 'Collection removed.',
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function addFile(Request $request, FileCollection $collection, File $file)
  {
    try {

      $collection->files()->syncWithoutDetaching([$file->id]);

      return response([
        'message' => 'Add file to collection.'
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function removeFile(Request $request, FileCollection $collection, File $file)
  {
    try {

      $collection->files()->detach($file->id);

      return response([
        'message' => 'Add files to collection.',
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
