<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPassword;
use App\Mail\UserVerificationMail;
use App\Models\Tbl_page;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * register Store Acount register details
     **/
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string',
            'website' => 'nullable',
            'email' => ['required', 'email', 'unique:users'],
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'error' => $validator->messages()
            ], 200);
        }

        $rememberToken = Str::random(60);
        $user = User::create([
            'business_name' => $request->business_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'website' => $request->website,
            'remember_token' => $rememberToken
        ]);
        if ($user)
        {
            $mailData = [
                'title' => 'Mail from DG Company',
                'body' => 'This is for testing email using smtp.',
                'token' => $user->remember_token,
                'business_name' => $user->business_name
            ];
    
            Mail::to($user->email)->send(new UserVerificationMail($mailData));
            
            $result = [
                'data' => $user,
                'message' => 'Account has created Successfully',
                'status' => 200,
                'error' => NULL
            ];
        }
        else
        {
            $result = [
                'data' => NULL,
                'message' => 'Something went wrong!',
                'status' => 200,
                'error' => [
                    'message' => 'Account Not Found',
                    'code' => 404,
                ]
            ];
        }
        return response()->json($result);
    }

    /**
     * mailVerification  Mail verification at regioster time
     **/
    public function mailVerification($token){
        if($token){
            $user = user::where('remember_token',$token)->first();
            if($user){
                user::find($user->id)->update(['email_verified_at' => Carbon::now(),'remember_token'=>NULL]);
                $status = true;
            }else{
                $status = false;
            }
        }else{
            $status = false;
        }
        if($status == true){
            return view('auth.verified_page');
        }else{
            return 'something went wrong';
        }
        
    }
    /**
     * resendVerificationMail  resend Mail verification
     **/
    public function resendVerificationMail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     =>  'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'error' => $validator->messages()
            ], 200);
        }
        $user = user::where('email',$request->email)->first();
        if(isset($user->email_verified_at)){
            $result = [
                'data' => NULL,
                'message' => 'Email already verified',
                'status' => 200,
                'error' => [
                    'message' => 'success',
                    'code' => 404,
                ]
            ];
        }else{
            $rememberToken = Str::random(60);
            $user = $user->update(['email_verified_at' => Carbon::now(),'remember_token'=>$rememberToken]);
            $user = user::where('email',$request->email)->first();
            $mailData = [
                'title' => 'Mail from DG Company',
                'body' => 'This mail for verify your email!',
                'token' => $user->remember_token,
                'business_name' => $user->business_name
            ];
    
            Mail::to($user->email)->send(new UserVerificationMail($mailData));
            $result = [
                'data' => NULL,
                'message' => 'Please check your mail to Verify email',
                'status' => 200,
                'error' => [
                    'message' => 'success',
                    'code' => 404,
                ]
            ];
        }
        return response()->json($result);
    }
     /**
     * login user with JWT tocken
     **/
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     =>  'required|email',
            'password'  => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'error' => $validator->messages()
            ], 200);
        }
        try {
            $token = JWTAuth::attempt([
                "email" => $request->email,
                "password" => $request->password
            ]);
            if (!empty($token)) {
                $user = User::where('email', $request->email)->first();
                return response()->json([
                    'message' => ' login successfully !',
                    'data' => $token,
                    'status' => 200,
                ]);
            } else {
                return response()->json([
                    'message' => 'invalid email or password !',
                    'status' => 200,
                ]);
            }
        } catch (Exception $ex) {
            return response()->json([
                'data' => NULL,
                'message' => 'Server Error -' . $ex->getMessage(),
                'status' => 200,
            ]);
        }
    }
    /**
     * send otp forgot password
     **/
    public function forgotPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email not found!',
                'status' => 200,
            ]);
        }

        $code = Str::random(6);
        $user->update([
            'otp' => $code
        ]);

        // Send email with code
        $mailData = [
            'title' => 'Reset Your Password',
            'body' => 'This mail To reset your password!',
            'otp' => Crypt::encrypt($code),
            'email' => $request->email,
        ];
        Mail::to($user->email)->send(new ForgotPassword($mailData));

        return response()->json([
            'message' => 'OTP has sent on your email!',
            'status' => 200,
        ]);
    }
    /**
     * verification forgot password
     **/
    public function verifyForgotPassword($otp){
        $otp = Crypt::decrypt($otp);
        $user = User::where('otp',$otp)->first();
        if($user){
            return response()->json([
                'status' => 1,
                'message' => 'OTP verify successfully!',
            ]);
        }else{
            return response()->json([
                'status' => 1,
                'message' => 'OTP verify successfully!',
            ]);
        }
    }
    /**
     *  forgot password
     **/
    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required|same:new_password',
        ], [
            'confirm_password.same' => 'The confirm password must match the new password.',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('otp',$request->otp)->get();
        
        if($user){
            User::where('otp',$request->otp)->update(['password' => Hash::make($request->new_password)]);
            return response()->json([
                'status' => 1,
                'message' => 'Password Updated successfully!',
            ]);    
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Password not Updated!',
            ]); 
        } 
    }
    /**
     * Change password
     **/
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|different:current_password',
            'confirm_password' => 'required|string|same:new_password',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }
        $user =Auth::guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 401);
        }
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();

        if($user){
            return response()->json([
                'status' => 1,
                'message' => 'Password changed successfully!',
            ]);    
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Password not changed!',
            ]); 
        } 
    }
    /**
     * Add page
     **/
    public function addPage(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'page_id' => 'integer',
            'site_id' => 'integer',
            'user_id' => 'integer',
            'page_name' => 'required',
            'page_type' => 'nullable|in:1,2,3,4,5',
            'parent_id' => 'nullable',
            'page_status' => 'nullable',
            'show_page_header_footer' => 'nullable|boolean',
            'menu_visibility' => 'nullable|in:1,2,3',
            'url_slug' => 'nullable|string',
            'page_title' => 'nullable|string',
            'page_description' => 'nullable|string',
            'hide_from_search_engines' => 'nullable|boolean',
            'link_url' => 'nullable|string',
            'open_in_new_window' => 'nullable|boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }
        $user =Auth::guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }
        $page = Tbl_page::create($request->all());

        if ($page) {
            return response()->json([
                'status' => 1,
                'message' => 'created page successfully!',
                'data' => $page,
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to create page!',
            ]);
        }
    }
    /**
     * site menu list (visibility)
     **/
    public function updateMenuVisibility(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:tbl_pages,id',
            'menu_visibility' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user =Auth::guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }
        $page = Tbl_page::where('page_id',$request->page_id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        Tbl_page::where('page_id',$request->page_id)->update([
            'menu_visibility'=> $request->menu_visibility,
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Menu visibility updated successfully!',
            'data' => $page,
        ]);
    }
    /**
     * Not in menu list (visibility)
     **/
    public function hideFromMenuList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:tbl_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        Tbl_page::where('page_id', $request->page_id)->update([
            'show_hide_menu' => 0,
        ]);

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        return response()->json([
            'status' => 1,
            'message' => 'Hide from menu list!',
            'data' => $page,
        ]);
    }
    /**
     * in menu list (visibility)
     **/
    public function showInMenuList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:tbl_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        Tbl_page::where('page_id', $request->page_id)->update([
            'show_hide_menu' => 1,
        ]);

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        return response()->json([
            'status' => 1,
            'message' => 'Menu Successfully added in menu list!',
            'data' => $page,
        ]);
    }

    public function renamePageName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:tbl_pages,id',
            'page_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        Tbl_page::where('page_id', $request->page_id)->update([
            'page_name' => $request->page_name,
        ]);

        $page = Tbl_page::where('page_id', $request->page_id)->first();

        return response()->json([
            'status' => 1,
            'message' => 'Page name updated successfully!',
            'data' => $page,
        ]);
    }

    public function deleteMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }

        $page = Tbl_page::where('id', $request->id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        // Delete the page
        $page->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Page deleted successfully!',
        ]);
    }

    public function updatePageOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:tbl_pages,id',
            'order' => 'required|integer',
            'parent_id' => 'nullable|exists:tbl_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fail',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }

        $page = Tbl_page::where('page_id',$request->page_id)->first();

        if (!$page) {
            return response()->json([
                'status' => 0,
                'message' => 'Page not found!',
            ], 404);
        }

        $page->order = $request->order;

        if ($request->has('parent_id')) {
            $page->parent_id = $request->parent_id;
        }

        $page->save();

        return response()->json([
            'status' => 1,
            'message' => 'Page order updated successfully!',
            'data' => $page,
        ]);
    }

    public function notInMenuList()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthenticated',
            ], 400);
        }
        $pages = Tbl_page::where('show_hide_menu', '!=', 1)->get();
        if($pages){
            return response()->json([
                'status' => 1,
                'message' => 'Successfully fetched data',
                'data' => $pages,
            ]);
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Data not found!',
            ]);
        }
        
    }



}
