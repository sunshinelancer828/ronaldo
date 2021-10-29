<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    
    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->get('tracking', 'HomeController@tracking');
    $router->get('get-vehicle-category', 'GeneralController@GetVehicleCategory');
    $router->get('get-drivers', 'GeneralController@GetDrivers');
    $router->get('get_complaint_category', 'GeneralController@GetComplaintCategory');
    $router->get('get_complaint_sub_category', 'GeneralController@GetComplaintSubCategory');
    $router->resource('app-settings', AppSettingController::class); 
    $router->resource('cancellation-reasons', CancellationReasonController::class); 
    $router->resource('twillio-settings', TwillioSettingController::class);
    $router->resource('cancellation-settings', CancellationSettingController::class);
    $router->resource('trip-settings', TripSettingController::class);
    $router->resource('faqs', FaqController::class);
    $router->resource('user-types', UserTypeController::class);
    $router->resource('privacy-policies', PrivacyPolicyController::class);
    $router->resource('payment-methods', PaymentMethodController::class);
    $router->resource('promo-codes', PromoCodeController::class);
    $router->resource('referral-settings', ReferralSettingController::class);
    $router->resource('vehicle-categories', VehicleCategoryController::class);
    $router->resource('complaint-categories', ComplaintCategoryController::class); 
    $router->resource('tax-lists', TaxListController::class);    
    $router->resource('mail-contents', MailContentController::class);
    $router->resource('complaints', ComplaintController::class);
    $router->resource('customers', CustomerController::class);    
    $router->resource('drivers', DriverController::class);
    $router->resource('driver-vehicles', DriverVehicleController::class);
    $router->resource('feature-settings', FeatureSettingController::class);
    $router->resource('contact-settings', ContactSettingController::class);
    $router->resource('complaint-sub-categories', ComplaintSubCategoryController::class);
    $router->resource('notification-messages', NotificationMessageController::class);
    $router->resource('messages', MessageController::class);
    $router->resource('countries', CountryController::class);
    $router->resource('currencies', CurrencyController::class);
    $router->resource('customer-wallet-histories', CustomerWalletHistoryController::class);
    $router->resource('driver-withdrawals', DriverWithdrawalController::class);
    $router->resource('driver-earnings', DriverEarningController::class);
    $router->resource('driver-wallet-histories', DriverWalletHistoryController::class);
    $router->resource('driver-bank-kyc-details', DriverBankKycDetailController::class);
    $router->resource('trips', TripController::class);
    $router->resource('driver-tutorials', DriverTutorialController::class);
    $router->resource('booking-statuses', BookingStatusController::class);
    $router->resource('customer-offers', CustomerOfferController::class);
    $router->resource('fare-management', FareManagementController::class);
    $router->resource('payment-types', PaymentTypeController::class);
    $router->resource('instant-offers', InstantOfferController::class);
    $router->resource('lucky-offers', LuckyOfferController::class);
    $router->resource('scratch-card-settings', ScratchCardSettingController::class);
    $router->get('get_offers', 'GeneralController@getOffers');
    $router->resource('driver-queries', DriverQueryController::class);
    $router->resource('customer-sos-contacts', CustomerSosContactController::class);
    $router->resource('trip-types', TripTypeController::class);
    $router->resource('daily-fare-managements', DailyFareManagementController::class);
    $router->resource('outstation-fare-managements', OutstationFareManagementController::class);
    $router->resource('packages', PackageController::class);
    $router->resource('rental-fare-managements', RentalFareManagementController::class);
    $router->resource('trip-request-statuses', TripRequestStatusController::class);
    $router->resource('driver-trip-requests', DriverTripRequestController::class);
    $router->resource('user-promo-histories', UserPromoHistoryController::class);
    $router->resource('trip-requests', TripRequestController::class);
    $router->resource('payment-histories', PaymentHistoryController::class);
    $router->resource('ratings', RatingsController::class);
    $router->resource('feature-settings', FeatureSettingController::class);
    $router->resource('statuses', StatusController::class);

});