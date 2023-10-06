<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class DashboardController extends Controller
{
  private function bytesToMB($bytes) {
    $gb = $bytes / 1024 / 1024; // / 1024
    return $gb;
  }

  private function bytesToGB($bytes) {
    $gb = $bytes / 1024 / 1024 / 1024;
    return $gb;
  }

  private function getTotalSizeOfFilesInDirectory($directoryPath){
    $finder = new Finder();
    $finder->files()->in($directoryPath);

    $totalSize = 0;

    foreach ($finder as $file) {
        $totalSize += File::size($file->getRealPath());
    }

    return $totalSize;
  }

  public function index(Request $request){
    try {
      $totalDiskSpace = disk_total_space('/');
      $freeDiskSpace = disk_free_space('/');

      return response([
        'message' => 'Dashboard.',
        'total_disk_space' => $totalDiskSpace,
        'free_disk_space' => $freeDiskSpace
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => 'Server error.'
      ], 500);
    }
  }
}