<?php

namespace App\Http\Controllers\tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;

use App\Models\tenant\MarketingMailingList;
use App\Models\tenant\MarketingMailers;

use App\Mail\SendHtmlMail;

class MarketingMailerController extends Controller
{
    public function index(Request $request)
    {
      try {
  
        $query = MarketingMailers::query();
        $lists = $query->get();
  
        return response([
          'message' => 'List of all marketing mailers.',
            'mailers' => $lists
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }
  
      public function show(Request $request, MarketingMailers $mailer)
    {
      try {
        return response([
            'message' => 'Single marketing mailer.',
            'mailer' => $mailer
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }
  
      public function store(Request $request)
    {
  
      $request->validate([
        'name' => ['string'],
        'unlayer_data' => ['json', 'nullable'],
        'html' => ['string', 'nullable'],
      ]);
  
      try {
  
        $mailer = MarketingMailers::create($request->all());
  
        return response([
            'message' => 'Created new marketing mailer.',
            'mailer' => $mailer
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }
  
      public function update(Request $request, MarketingMailers $mailer)
    {
  
      $request->validate([
        'name' => ['string'],
        'subject' => ['string'],
        'unlayer_data' => ['json'],
        'html' => ['string'],
      ]);
  
      try {
  
        $mailer->update($request->all());
  
        return response([
            'message' => 'Updated marketing mailer.',
            'mailer' => $mailer
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }
  
    public function destroy(Request $request, MarketingMailers $mailer)
    {
  
      $request->validate([
        'public' => ['boolean'],
        'blocked' => ['boolean'],
      ]);
  
      try {
  
        $mailer->delete();
  
        return response([
          'message' => 'Marketing mailer destroyed.'
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }

    public function send(Request $request, MarketingMailers $mailer, MarketingMailingList $mailingList)
    {  
      try {

        foreach($mailingList->subscribers as $subscriber){
          Mail::to($subscriber->email)->send(new SendHtmlMail($mailer, $mailingList, $subscriber));
        }
        
        return response([
          'message' => 'Marketing mailer sent to mailing list.'
        ], 200);
      } catch (Exception $e) {
        return response([
          'message' => $e->getMessage()
        ], 500);
      }
    }
}
