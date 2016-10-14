<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('article/{article_id}', 'ArticleController@getArticle');
Route::get('about/contact-us', 'AboutController@contactUs');
Route::get('about/introduction', 'AboutController@introduction');
Route::get('about/lawyer', 'AboutController@lawyer');

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers'], function ($api) {
        /**
         * Api Doc
         */
        $api->get('/', 'ApiController@index');

        /**
         * Register & Login
         */
        $api->group(['prefix' => 'user'], function ($api) {
            $api->post('register', 'AuthController@register');
            $api->post('verify-code', 'AuthController@sendVerifyCode');
            $api->post('inviter', 'AuthController@getInviter');
            $api->post('login', 'AuthController@authenticate');
            $api->post('reset-pwd', 'AuthController@resetPassword');
        });

        /**
         * Token Auth
         */
        $api->group(['middleware' => 'jwt.auth'], function ($api) {
            // Init
            $api->group(['prefix' => 'init'], function ($api) {
                $api->get('/', 'InitController@index');
            });

            // Doctor
            $api->group(['prefix' => 'user'], function ($api) {
                $api->get('me', 'AuthController@getAuthenticatedUser');
                $api->get('phone/{doctor}', 'UserController@findDoctor_byPhone');
                $api->post('/', 'UserController@update');
            });

            // Search
            $api->group(['prefix' => 'search'], function ($api) {
                $api->post('/', 'SearchController@searchUser');
                $api->get('default', 'SearchController@index');
                $api->get('doctor/{doctor}', 'SearchController@findDoctor');
                $api->get('my-doctor', 'SearchController@findMyDoctor');
                $api->post('admissions', 'SearchController@searchUser_admissions');
                $api->post('same-hospital', 'SearchController@searchUser_sameHospital');
                $api->post('same-department', 'SearchController@searchUser_sameDept');
                $api->post('same-college', 'SearchController@searchUser_sameCollege');
            });

            // City
            $api->group(['prefix' => 'city'], function ($api) {
                $api->get('/', 'CityController@index');
                $api->get('group', 'CityController@cityGroup');
            });

            // Hospital
            $api->group(['prefix' => 'hospital'], function ($api) {
                $api->get('/', 'HospitalsController@index');
                $api->get('city/{city}', 'HospitalsController@inCityHospital');
                $api->get('{hospital}', 'HospitalsController@show');
                $api->get('search/{search_field}', 'HospitalsController@findHospital');
                $api->post('search/admissions', 'HospitalsController@findHospital_provinces');
            });

            // College
            $api->group(['prefix' => 'college'], function ($api) {
                $api->get('/all', 'CollegeController@index');
            });

            // Dept
            $api->group(['prefix' => 'dept'], function ($api) {
                $api->get('/', 'DeptStandardController@index');
            });
            
            // Tag
            $api->group(['prefix' => 'tag'], function ($api) {
                $api->get('/all', 'TagController@index');
                $api->post('/illness', 'TagController@getIllness');
                $api->get('/group', 'TagController@group');
            });

            // Radio
            $api->group(['prefix' => 'radio'], function ($api) {
                $api->get('/', 'RadioStationController@index');
                $api->post('read', 'RadioStationController@readStatus');
            });

            //Appointment
            $api->group(['prefix' => 'appointment'], function ($api) {
                $api->post('new', 'AppointmentController@store');
                $api->post('instead', 'AppointmentController@insteadAppointment');
                $api->post('upload-img', 'AppointmentController@uploadImg');
                $api->post('detail', 'AppointmentController@getDetailInfo');
                $api->get('list', 'AppointmentController@getReservationRecord');

                $api->post('pay', 'AppointmentController@pay');
                $api->post('complete', 'AppointmentController@complete');
            });
            
            //Admissions
            $api->group(['prefix' => 'admissions'], function ($api) {
                $api->get('list', 'AdmissionsController@getAdmissionsRecord');
                $api->get('detail/{admissions}', 'AdmissionsController@getDetailInfo');
                $api->post('agree', 'AdmissionsController@agreeAdmissions');
                $api->post('refusal', 'AdmissionsController@refusalAdmissions');
                $api->post('complete', 'AdmissionsController@completeAdmissions');
                $api->post('rescheduled', 'AdmissionsController@rescheduledAdmissions');
                $api->post('cancel', 'AdmissionsController@cancelAdmissions');
                $api->post('transfer', 'AdmissionsController@transferAdmissions');
            });

            //Patient
            $api->group(['prefix' => 'patient'], function ($api) {
                $api->get('get-by-phone', 'PatientController@getInfoByPhone');
            });

            //Face-to-face
            $api->group(['prefix' => 'f2f-advice'], function ($api) {
                $api->post('new', 'FaceToFaceAdviceController@store');
            });

            //Message
            $api->group(['prefix' => 'msg'], function ($api) {
                $api->get('appointment/all', 'AppointmentMsgController@index');
                $api->get('appointment/new', 'AppointmentMsgController@newMessage');
                $api->post('appointment/read', 'AppointmentMsgController@readMessage');
                $api->get('admissions/all', 'AdmissionsMsgController@index');
                $api->get('admissions/new', 'AdmissionsMsgController@newMessage');
                $api->post('admissions/read', 'AdmissionsMsgController@readMessage');
            });

            //Contacts
            $api->group(['prefix' => 'contacts'], function ($api) {
                $api->get('all', 'ContactController@index');
            });

            //Pay
            $api->group(['prefix' => 'pay'], function ($api) {
                $api->get('/', 'PayController@pay');
                $api->get('notify_url', 'PayController@notifyUrl');
            });
        });
    });
});
