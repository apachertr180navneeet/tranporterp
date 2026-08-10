<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\BiltyAdvanceDetailController;
use App\Http\Controllers\Admin\DriverManagementController;

use App\Http\Controllers\Admin\LetterheadController;

use App\Http\Controllers\Admin\Document\DocumentDashboardController;
use App\Http\Controllers\Admin\Document\DocumentCategoryController;
use App\Http\Controllers\Admin\Document\DocumentFolderController;
use App\Http\Controllers\Admin\Document\DocumentController;
use App\Http\Controllers\Admin\Document\DocumentVersionController;
use App\Http\Controllers\Admin\Document\DocumentActivityController;
use App\Http\Controllers\Admin\Document\DocumentReportController;

use App\Http\Controllers\Admin\Transport\BultyController;
use App\Http\Controllers\Admin\Transport\TripController;

use App\Http\Controllers\Admin\Masters\ConsignorController;
use App\Http\Controllers\Admin\Masters\ConsigneeController;
use App\Http\Controllers\Admin\Masters\VehicleController;
use App\Http\Controllers\Admin\Masters\DriverController;
use App\Http\Controllers\Admin\Masters\GstMasterController;
use App\Http\Controllers\Admin\Masters\CityController;
use App\Http\Controllers\Admin\Masters\PackagingController;
use App\Http\Controllers\Admin\Masters\UnitController;
use App\Http\Controllers\Admin\Masters\FuelPumpController;
use App\Http\Controllers\Admin\Masters\FuelCompanyController;
use App\Http\Controllers\Admin\Masters\AdBlueCompanyController;
use App\Http\Controllers\Admin\Masters\ItemController;
use App\Http\Controllers\Admin\Masters\SupplierController;
use App\Http\Controllers\Admin\Masters\VendorController;
use App\Http\Controllers\Admin\Masters\BankMasterController;
use App\Http\Controllers\Admin\Masters\BankBranchMasterController;
use App\Http\Controllers\Admin\Masters\BillFormatController;

use App\Http\Controllers\Front\WebsiteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [WebsiteController::class, 'landing'])->name('landing');
Route::get('/home', [WebsiteController::class, 'home']);
Route::get('/login', [AdminAuthController::class, 'login'])->name('front.login');
Route::post('/login', [AdminAuthController::class, 'postLogin'])->name('front.login.post');
Route::get('/about', [WebsiteController::class, 'about'])->name('about');
Route::get('/services', [WebsiteController::class, 'services'])->name('services');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('contact');
Route::post('/contact', [WebsiteController::class, 'submitContact']);
Route::get('/tracking', [WebsiteController::class, 'tracking'])->name('tracking');
Route::post('/tracking', [WebsiteController::class, 'tracking']);
Route::get('/bilty/{share_token}', [WebsiteController::class, 'showBilty'])->name('bilty.share');
Route::get('/bilty/{share_token}/pdf', [WebsiteController::class, 'downloadBiltyPdf'])->name('bilty.pdf');
Route::post('/bilty/{share_token}/upload-document', [WebsiteController::class, 'uploadMaterialDocument'])->name('bilty.upload-document');
Route::post('/bilty/{share_token}/upload-pod', [WebsiteController::class, 'uploadPodDocument'])->name('bilty.upload-pod');

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::name('admin.')->prefix('admin')->group(function () {
    Route::get('/', [AdminAuthController::class, 'index']);
    Route::get('login', [AdminAuthController::class, 'login'])->name('login');
    Route::post('login', [AdminAuthController::class, 'postLogin'])->name('login.post');
    Route::get('forget-password', [AdminAuthController::class, 'showForgetPasswordForm'])->name('forget.password.get');
    Route::post('forget-password', [AdminAuthController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
    Route::get('reset-password/{token}', [AdminAuthController::class, 'showResetPasswordForm'])->name('reset.password.get');
    Route::post('reset-password', [AdminAuthController::class, 'submitResetPasswordForm'])->name('reset.password.post');

    Route::middleware(['admin'])->group(function () {
        Route::get('dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        Route::get('change-password', [AdminAuthController::class, 'changePassword'])->name('change.password');
        Route::post('update-password', [AdminAuthController::class, 'updatePassword'])->name('update.password');
        Route::get('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('profile', [AdminAuthController::class, 'adminProfile'])->name('profile');
        Route::post('profile', [AdminAuthController::class, 'updateAdminProfile'])->name('update.profile');

        Route::get('activity-logs', [SuperAdminController::class, 'activityLogs'])->name('activity-logs');
        Route::get('settings', [SuperAdminController::class, 'systemSettings'])->name('settings');
        Route::post('settings', [SuperAdminController::class, 'updateSettings'])->name('settings.update');

        Route::get('companies/all', [CompanyController::class, 'getAllCompanies'])->name('companies.all');
        Route::get('companies/{company}/branches', [CompanyController::class, 'getBranches'])->name('companies.branches');
        Route::post('switch-company', [\App\Http\Controllers\Admin\SuperAdminController::class, 'switchCompany'])->name('switch-company');
        Route::post('switch-year', [\App\Http\Controllers\Admin\SuperAdminController::class, 'switchYear'])->name('switch-year');
        Route::post('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
        Route::get('companies/trashed', [CompanyController::class, 'trashed'])->name('companies.trashed');
        Route::put('companies/{id}/restore', [CompanyController::class, 'restore'])->name('companies.restore');
        Route::delete('companies/{id}/force-delete', [CompanyController::class, 'forceDelete'])->name('companies.force-delete');
        Route::resource('companies', CompanyController::class);
        Route::get('companies/{company}/details', [LetterheadController::class, 'getCompanyDetails'])->name('companies.details');

        Route::get('letterheads/{letterhead}/pdf', [LetterheadController::class, 'pdf'])->name('letterheads.pdf');
        Route::post('letterheads/{letterhead}/send-mail', [LetterheadController::class, 'sendMail'])->name('letterheads.send-mail');
        Route::resource('letterheads', LetterheadController::class);

        Route::get('branches/all', [BranchController::class, 'getAllBranches'])->name('branches.all');
        Route::post('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');
        Route::get('branches/trashed', [BranchController::class, 'trashed'])->name('branches.trashed');
        Route::put('branches/{id}/restore', [BranchController::class, 'restore'])->name('branches.restore');
        Route::delete('branches/{id}/force-delete', [BranchController::class, 'forceDelete'])->name('branches.force-delete');
        Route::resource('branches', BranchController::class);

        Route::post('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])->name('roles.assign-permissions');
        Route::resource('roles', RoleController::class);

        Route::post('permissions/bulk', [PermissionController::class, 'bulkStore'])->name('permissions.bulk');
        Route::resource('permissions', PermissionController::class);

        Route::get('users/get-branches/{companyId?}', [UserController::class, 'getBranchesByCompany'])->name('users.get-branches');
        Route::get('users/get-cities/{state?}', [UserController::class, 'getCitiesByState'])->name('users.get-cities');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('users', UserController::class);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/vehicle', [ReportController::class, 'vehicleReport'])->name('reports.vehicle');
        Route::get('reports/trip', [ReportController::class, 'tripReport'])->name('reports.trip');
        Route::get('reports/driver-trip', [ReportController::class, 'driverTripReport'])->name('reports.driver-trip');
        Route::get('reports/customer-ledger', [ReportController::class, 'customerLedger'])->name('reports.customer-ledger');
        Route::get('reports/trip-reports', [ReportController::class, 'tripReports'])->name('reports.trip-reports');
        Route::get('reports/fuel', [ReportController::class, 'fuelReport'])->name('reports.fuel');
        Route::get('reports/adblue', [ReportController::class, 'adblueReport'])->name('reports.adblue');
        Route::get('reports/fuel-outstanding', [TripController::class, 'fuelOutstanding'])->name('reports.fuel-outstanding');
        Route::get('reports/adblue-outstanding', [TripController::class, 'adblueOutstanding'])->name('reports.adblue-outstanding');
        Route::get('reports/vehicle-utilization', [ReportController::class, 'vehicleUtilization'])->name('reports.vehicle-utilization');
        Route::get('reports/mis', [ReportController::class, 'misReport'])->name('reports.mis');
        Route::get('reports/expense-management', [ReportController::class, 'expenseReport'])->name('reports.expense-management');
        Route::get('reports/vehicle-documents', [ReportController::class, 'vehicleDocumentReport'])->name('reports.vehicle-documents');
        Route::get('reports/gst-tax', [ReportController::class, 'gstTaxReport'])->name('reports.gst-tax');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLossReport'])->name('reports.profit-loss');
        Route::get('reports/users', [ReportController::class, 'usersReport'])->name('reports.users');
        Route::get('reports/companies', [ReportController::class, 'companiesReport'])->name('reports.companies');
        Route::get('reports/activity', [ReportController::class, 'activityReport'])->name('reports.activity');
        Route::get('reports/roles', [ReportController::class, 'rolesReport'])->name('reports.roles');

        Route::get('reports/sales-ledger', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'index'])->name('reports.sales-ledger');
        Route::get('reports/sales-ledger/export-excel', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'exportExcel'])->name('reports.sales-ledger.export-excel');
        Route::get('reports/sales-ledger/history', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'history'])->name('reports.sales-ledger.history');
        Route::post('reports/sales-ledger/receive', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'storeReceiving'])->name('reports.sales-ledger.receive');
        Route::get('reports/sales-ledger/invoice-details/{id}', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'getInvoiceDetails'])->name('reports.sales-ledger.invoice-details');
        Route::get('reports/tds-report', [\App\Http\Controllers\Admin\SalesLedgerController::class, 'tdsReport'])->name('reports.tds-report');
        Route::get('reports/bilty-advance-details', [BiltyAdvanceDetailController::class, 'index'])->name('reports.bilty-advance-details.index');
        Route::post('reports/bilty-advance-details', [BiltyAdvanceDetailController::class, 'store'])->name('reports.bilty-advance-details.store');
        Route::put('reports/bilty-advance-details/{id}', [BiltyAdvanceDetailController::class, 'update'])->name('reports.bilty-advance-details.update');
        Route::delete('reports/bilty-advance-details/{id}', [BiltyAdvanceDetailController::class, 'destroy'])->name('reports.bilty-advance-details.destroy');
        Route::get('reports/bilty-advance-details/export', [BiltyAdvanceDetailController::class, 'exportExcel'])->name('reports.bilty-advance-details.export');
        Route::get('reports/bilty-advance-details/bulty-info/{id}', [BiltyAdvanceDetailController::class, 'getBultyInfo'])->name('reports.bilty-advance-details.bulty-info');

        Route::get('reports/vehicle/export/{format}', [ReportController::class, 'exportVehicle'])->name('reports.vehicle.export');
        Route::get('reports/trip/export/{format}', [ReportController::class, 'exportTrip'])->name('reports.trip.export');
        Route::get('reports/driver-trip/export/{format}', [ReportController::class, 'exportDriverTrip'])->name('reports.driver-trip.export');
        Route::get('reports/customer-ledger/export/{format}', [ReportController::class, 'exportCustomerLedger'])->name('reports.customer-ledger.export');
        Route::get('reports/trip-reports/export/{format}', [ReportController::class, 'exportTripReports'])->name('reports.trip-reports.export');
        Route::get('reports/fuel/export/{format}', [ReportController::class, 'exportFuel'])->name('reports.fuel.export');
        Route::get('reports/adblue/export/{format}', [ReportController::class, 'exportAdBlue'])->name('reports.adblue.export');
        Route::get('reports/vehicle-utilization/export/{format}', [ReportController::class, 'exportVehicleUtilization'])->name('reports.vehicle-utilization.export');
        Route::get('reports/mis/export/{format}', [ReportController::class, 'exportMis'])->name('reports.mis.export');
        Route::get('reports/expense-management/export/{format}', [ReportController::class, 'exportExpense'])->name('reports.expense-management.export');
        Route::get('reports/vehicle-documents/export/{format}', [ReportController::class, 'exportVehicleDocuments'])->name('reports.vehicle-documents.export');
        Route::get('reports/gst-tax/export/{format}', [ReportController::class, 'exportGstTax'])->name('reports.gst-tax.export');
        Route::get('reports/profit-loss/export/{format}', [ReportController::class, 'exportProfitLoss'])->name('reports.profit-loss.export');
        Route::get('reports/users/export', [ReportController::class, 'exportUsers'])->name('reports.users.export');

        /*
        |--------------------------------------------------------------------------
        | Document Management Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('dashboard', [DocumentDashboardController::class, 'index'])->name('dashboard');

            Route::resource('categories', DocumentCategoryController::class)->except(['create', 'show', 'edit']);
            Route::resource('folders', DocumentFolderController::class)->except(['create', 'show', 'edit']);

            Route::get('trash', [DocumentController::class, 'trash'])->name('trash');
            Route::put('{id}/restore', [DocumentController::class, 'restore'])->name('restore');
            Route::delete('{id}/force-delete', [DocumentController::class, 'forceDelete'])->name('force-delete');

            Route::post('bulk-action', [DocumentController::class, 'bulkAction'])->name('bulk-action');
            Route::get('activity-logs', [DocumentActivityController::class, 'index'])->name('activity-logs');

            Route::get('reports/expiry', [DocumentReportController::class, 'expiry'])->name('reports.expiry');
            Route::get('reports/storage', [DocumentReportController::class, 'storage'])->name('reports.storage');

            Route::get('{document}/preview', [DocumentController::class, 'preview'])->name('preview');
            Route::get('{document}/download', [DocumentController::class, 'download'])->name('download');

            Route::post('{document}/versions', [DocumentVersionController::class, 'store'])->name('versions.store');
            Route::get('versions/{version}/download', [DocumentVersionController::class, 'download'])->name('versions.download');

            Route::resource('/', DocumentController::class)->parameters(['' => 'document']);
        });
        Route::get('reports/companies/export', [ReportController::class, 'exportCompanies'])->name('reports.companies.export');
        Route::get('reports/activity/export', [ReportController::class, 'exportActivity'])->name('reports.activity.export');

        Route::name('driver-management.')->prefix('driver-management')->group(function () {
            Route::get('salary', [DriverManagementController::class, 'salaryManagement'])->name('salary');
            Route::post('salary', [DriverManagementController::class, 'storeSalary'])->name('salary.store');
            Route::get('salary/{salary}/edit', [DriverManagementController::class, 'editSalary'])->name('salary.edit');
            Route::put('salary/{salary}', [DriverManagementController::class, 'updateSalary'])->name('salary.update');
            Route::get('advance', [DriverManagementController::class, 'advanceManagement'])->name('advance');
            Route::post('advance', [DriverManagementController::class, 'storeAdvance'])->name('advance.store');
            Route::get('advance/{advance}/edit', [DriverManagementController::class, 'editAdvance'])->name('advance.edit');
            Route::put('advance/{advance}', [DriverManagementController::class, 'updateAdvance'])->name('advance.update');
            Route::delete('advance/{advance}', [DriverManagementController::class, 'destroyAdvance'])->name('advance.destroy');
            Route::get('salary-slip', [DriverManagementController::class, 'salarySlip'])->name('salary-slip');
            Route::delete('salary-slip/{salarySlip}', [DriverManagementController::class, 'destroySalarySlip'])->name('salary-slip.destroy');
            Route::get('salary-slip-list', [DriverManagementController::class, 'salarySlipList'])->name('salary-slip.list');
        });

        Route::name('leaves.')->prefix('leaves')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\Admin\LeaveController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\LeaveController::class, 'store'])->name('store');
            Route::post('{id}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('approve');
            Route::post('{id}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('reject');
        });

        Route::name('attendance.')->prefix('attendance')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('index');
            Route::post('check-in', [\App\Http\Controllers\Admin\AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('check-out', [\App\Http\Controllers\Admin\AttendanceController::class, 'checkOut'])->name('check-out');
            Route::post('mark', [\App\Http\Controllers\Admin\AttendanceController::class, 'markAttendance'])->name('mark');
        });

        Route::name('advances.')->prefix('advances')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdvanceController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\Admin\AdvanceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdvanceController::class, 'store'])->name('store');
            Route::post('{id}/approve', [\App\Http\Controllers\Admin\AdvanceController::class, 'approve'])->name('approve');
            Route::post('{id}/reject', [\App\Http\Controllers\Admin\AdvanceController::class, 'reject'])->name('reject');
            Route::post('{id}/mark-paid', [\App\Http\Controllers\Admin\AdvanceController::class, 'markPaid'])->name('mark-paid');
        });

        Route::name('employee-salary.')->prefix('employee-salary')->group(function () {
            Route::get('employees-list', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'index'])->name('employees-list');
            Route::get('employees-list/{id}/details', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'details'])->name('details');
            Route::get('employees-list/{id}/edit-structure', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'editStructure'])->name('edit-structure');
            Route::post('employees-list/{id}/edit-structure', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'updateStructure'])->name('update-structure');
            Route::get('employees-list/{id}/revisions', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'revisions'])->name('revisions');
            Route::post('employees-list/{id}/apply-revision', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'applyRevision'])->name('apply-revision');
            Route::post('employees-list/{id}/incentive', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'storeIncentive'])->name('store-incentive');
            Route::post('employees-list/{id}/process-salary', [\App\Http\Controllers\Admin\EmployeeSalaryController::class, 'processSalary'])->name('process-salary');
        });

        Route::name('transport.')->prefix('transport')->group(function () {
            
            Route::get('bulties/trashed', [BultyController::class, 'trashed'])->name('bulties.trashed');
            Route::get('bulties/next-lr/{branchId}', [BultyController::class, 'nextLRNumber'])->name('bulties.next-lr');
            Route::post('bulties/{bulty}/reject', [BultyController::class, 'reject'])->name('bulties.reject');
            Route::put('bulties/{bulty}/restore', [BultyController::class, 'restore'])->name('bulties.restore');
            Route::delete('bulties/{bulty}/force-delete', [BultyController::class, 'forceDelete'])->name('bulties.force-delete');
            Route::resource('bulties', BultyController::class);
            Route::post('bulties/{bulty}/approve-document', [BultyController::class, 'approveDocument'])->name('bulties.approve-document');
            Route::post('bulties/{bulty}/reject-document', [BultyController::class, 'rejectDocument'])->name('bulties.reject-document');
            Route::post('bulties/{bulty}/approve-pod', [BultyController::class, 'approvePodDocument'])->name('bulties.approve-pod');
            Route::post('bulties/{bulty}/reject-pod', [BultyController::class, 'rejectPodDocument'])->name('bulties.reject-pod');
            Route::get('bulties/{bulty}/pdf', [BultyController::class, 'generatePdf'])->name('bulties.pdf');
            Route::get('bulties/{bulty}/print-bill', [BultyController::class, 'printBill'])->name('bulties.print-bill');
            Route::post('bulties/{bulty}/send-mail', [BultyController::class, 'sendMail'])->name('bulties.send-mail');

            Route::get('trips', [TripController::class, 'index'])->name('trips.index');
            Route::get('trips/create/{builty}', [TripController::class, 'create'])->name('trips.create');
            Route::post('trips', [TripController::class, 'store'])->name('trips.store');
            Route::get('trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
            Route::put('trips/{trip}', [TripController::class, 'update'])->name('trips.update');
            Route::post('trips/{trip}/toggle-status', [TripController::class, 'toggleStatus'])->name('trips.toggle-status');
            Route::get('trips/fast-tag/download-template', [TripController::class, 'downloadFastTagTemplate'])->name('trips.fast-tag.download-template');
            Route::post('trips/fast-tag/import', [TripController::class, 'importFastTag'])->name('trips.fast-tag.import');
            Route::get('trips/fuel-detail/download-template', [TripController::class, 'downloadFuelDetailTemplate'])->name('trips.fuel-detail.download-template');
            Route::post('trips/fuel-detail/import', [TripController::class, 'importFuelDetail'])->name('trips.fuel-detail.import');
            Route::get('trips/adblue-detail/download-template', [TripController::class, 'downloadAdBlueDetailTemplate'])->name('trips.adblue-detail.download-template');
            Route::post('trips/adblue-detail/import', [TripController::class, 'importAdBlueDetail'])->name('trips.adblue-detail.import');
            Route::get('trips/other-amount-detail/download-template', [TripController::class, 'downloadOtherAmountDetailTemplate'])->name('trips.other-amount-detail.download-template');
            Route::post('trips/other-amount-detail/import', [TripController::class, 'importOtherAmountDetail'])->name('trips.other-amount-detail.import');
            Route::get('trips/advance-detail/download-template', [TripController::class, 'downloadAdvanceDetailTemplate'])->name('trips.advance-detail.download-template');
            Route::post('trips/advance-detail/import', [TripController::class, 'importAdvanceDetail'])->name('trips.advance-detail.import');
            Route::get('trips/pumps-by-company/{companyId}', [TripController::class, 'getPumpsByCompany'])->name('trips.pumps-by-company');

            Route::get('trips/fuel-outstanding', [TripController::class, 'fuelOutstanding'])->name('trips.fuel-outstanding');
            Route::post('trips/fuel-payments', [TripController::class, 'storeFuelPayment'])->name('trips.fuel-payments.store');
            Route::get('trips/fuel-payments/{id}/edit', [TripController::class, 'editFuelPayment'])->name('trips.fuel-payments.edit');
            Route::put('trips/fuel-payments/{id}', [TripController::class, 'updateFuelPayment'])->name('trips.fuel-payments.update');
            Route::delete('trips/fuel-payments/{id}', [TripController::class, 'destroyFuelPayment'])->name('trips.fuel-payments.destroy');

            Route::get('trips/adblue-outstanding', [TripController::class, 'adblueOutstanding'])->name('trips.adblue-outstanding');
            Route::post('trips/adblue-payments', [TripController::class, 'storeAdBluePayment'])->name('trips.adblue-payments.store');
            Route::get('trips/adblue-payments/{id}/edit', [TripController::class, 'editAdBluePayment'])->name('trips.adblue-payments.edit');
            Route::put('trips/adblue-payments/{id}', [TripController::class, 'updateAdBluePayment'])->name('trips.adblue-payments.update');
            Route::delete('trips/adblue-payments/{id}', [TripController::class, 'destroyAdBluePayment'])->name('trips.adblue-payments.destroy');

            Route::get('billing', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'index'])->name('billing');
            Route::get('billing/create', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'create'])->name('billing.create');
            Route::post('billing/generate', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'generate'])->name('billing.generate');
            Route::get('invoices', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'invoiceHistory'])->name('invoices.index');
            Route::get('invoices/{invoice}', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'showInvoice'])->name('invoices.show');
            Route::get('invoices/{invoice}/export-excel', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'exportInvoiceExcel'])->name('invoices.export-excel');
            Route::get('invoices/{invoice}/bill-generate', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'billGenerate'])->name('invoices.bill-generate');
            Route::get('invoices/{invoice}/toll-print', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'tollPrint'])->name('invoices.toll-print');
            Route::post('invoices/{invoice}/save-toll', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'saveTollInvoice'])->name('invoices.save-toll');
            Route::post('invoices/{invoice}/update-status', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'updateStatus'])->name('invoices.update-status');
            Route::delete('invoices/{invoice}', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'destroy'])->name('invoices.destroy');
            Route::get('toll-bills', [\App\Http\Controllers\Admin\Transport\BillingController::class, 'tollBills'])->name('toll-bills.index');
        });

        Route::name('masters.')->prefix('masters')->group(function () {
            Route::get('consignors/trashed', [ConsignorController::class, 'trashed'])->name('consignors.trashed');
            Route::put('consignors/{id}/restore', [ConsignorController::class, 'restore'])->name('consignors.restore');
            Route::delete('consignors/{id}/force-delete', [ConsignorController::class, 'forceDelete'])->name('consignors.force-delete');
            Route::get('consignors/download-template', [ConsignorController::class, 'downloadTemplate'])->name('consignors.download-template');
            Route::post('consignors/import', [ConsignorController::class, 'import'])->name('consignors.import');
            Route::resource('consignors', ConsignorController::class)->except(['show']);
            Route::get('consignors/{consignor}/transfer', [ConsignorController::class, 'transferForm'])->name('consignors.transfer');
            Route::put('consignors/{consignor}/transfer', [ConsignorController::class, 'transfer'])->name('consignors.transfer.update');
            Route::post('consignors/{consignor}/toggle-status', [ConsignorController::class, 'toggleStatus'])->name('consignors.toggle-status');
            Route::get('consignors/search/autocomplete', [ConsignorController::class, 'search'])->name('consignors.search');
            Route::post('consignors/quick-store', [ConsignorController::class, 'quickStore'])->name('consignors.quick-store');
            
            Route::get('consignees/download-template', [ConsigneeController::class, 'downloadTemplate'])->name('consignees.download-template');
            Route::post('consignees/import', [ConsigneeController::class, 'import'])->name('consignees.import');
            Route::get('consignees/trashed', [ConsigneeController::class, 'trashed'])->name('consignees.trashed');
            Route::put('consignees/{id}/restore', [ConsigneeController::class, 'restore'])->name('consignees.restore');
            Route::delete('consignees/{id}/force-delete', [ConsigneeController::class, 'forceDelete'])->name('consignees.force-delete');
            Route::resource('consignees', ConsigneeController::class)->except(['show']);
            Route::get('consignees/{consignee}/transfer', [ConsigneeController::class, 'transferForm'])->name('consignees.transfer');
            Route::put('consignees/{consignee}/transfer', [ConsigneeController::class, 'transfer'])->name('consignees.transfer.update');
            Route::post('consignees/{consignee}/toggle-status', [ConsigneeController::class, 'toggleStatus'])->name('consignees.toggle-status');
            Route::get('consignees/search/autocomplete', [ConsigneeController::class, 'search'])->name('consignees.search');
            Route::post('consignees/quick-store', [ConsigneeController::class, 'quickStore'])->name('consignees.quick-store');
            
            Route::get('vehicles/download-template', [VehicleController::class, 'downloadTemplate'])->name('vehicles.download-template');
            Route::get('vehicles/export', [VehicleController::class, 'export'])->name('vehicles.export');
            Route::post('vehicles/import', [VehicleController::class, 'import'])->name('vehicles.import');
            Route::get('vehicles/trashed', [VehicleController::class, 'trashed'])->name('vehicles.trashed');
            Route::put('vehicles/{id}/restore', [VehicleController::class, 'restore'])->name('vehicles.restore');
            Route::delete('vehicles/{id}/force-delete', [VehicleController::class, 'forceDelete'])->name('vehicles.force-delete');
            Route::resource('vehicles', VehicleController::class)->except(['show']);
            Route::post('vehicles/{vehicle}/toggle-status', [VehicleController::class, 'toggleStatus'])->name('vehicles.toggle-status');
            Route::get('vehicles/fetch/details', [VehicleController::class, 'getDetailsByNumber'])->name('vehicles.fetch-details');
            Route::get('vehicles/search/autocomplete', [VehicleController::class, 'search'])->name('vehicles.search');
            Route::post('vehicles/quick-store', [VehicleController::class, 'quickStore'])->name('vehicles.quick-store');
            
            Route::get('drivers/download-template', [DriverController::class, 'downloadTemplate'])->name('drivers.download-template');
            Route::get('drivers/export', [DriverController::class, 'export'])->name('drivers.export');
            Route::post('drivers/import', [DriverController::class, 'import'])->name('drivers.import');
            Route::get('drivers/trashed', [DriverController::class, 'trashed'])->name('drivers.trashed');
            Route::put('drivers/{id}/restore', [DriverController::class, 'restore'])->name('drivers.restore');
            Route::delete('drivers/{id}/force-delete', [DriverController::class, 'forceDelete'])->name('drivers.force-delete');
            Route::resource('drivers', DriverController::class)->except(['show']);
            Route::post('drivers/{driver}/toggle-status', [DriverController::class, 'toggleStatus'])->name('drivers.toggle-status');
            Route::get('drivers/fetch/details', [DriverController::class, 'getDetailsByName'])->name('drivers.fetch-details');
            Route::get('drivers/search/autocomplete', [DriverController::class, 'search'])->name('drivers.search');
            Route::post('drivers/quick-store', [DriverController::class, 'quickStore'])->name('drivers.quick-store');
            
            Route::get('gst/download-template', [GstMasterController::class, 'downloadTemplate'])->name('gst.download-template');
            Route::post('gst/import', [GstMasterController::class, 'import'])->name('gst.import');
            Route::get('gst/trashed', [GstMasterController::class, 'trashed'])->name('gst.trashed');
            Route::put('gst/{gst}/restore', [GstMasterController::class, 'restore'])->name('gst.restore');
            Route::delete('gst/{gst}/force-delete', [GstMasterController::class, 'forceDelete'])->name('gst.force-delete');
            Route::post('gst/{gst}/toggle-status', [GstMasterController::class, 'toggleStatus'])->name('gst.toggle-status');
            Route::resource('gst', GstMasterController::class);
            Route::get('city/download-template', [CityController::class, 'downloadTemplate'])->name('city.download-template');
            Route::post('city/import', [CityController::class, 'import'])->name('city.import');
            Route::get('city/trashed', [CityController::class, 'trashed'])->name('city.trashed');
            Route::put('city/{city}/restore', [CityController::class, 'restore'])->name('city.restore');
            Route::delete('city/{city}/force-delete', [CityController::class, 'forceDelete'])->name('city.force-delete');
            Route::post('city/{city}/toggle-status', [CityController::class, 'toggleStatus'])->name('city.toggle-status');
            Route::get('city/search', [CityController::class, 'search'])->name('city.search');
            Route::resource('city', CityController::class);
            Route::post('city/quick-store', [CityController::class, 'quickStore'])->name('city.quick-store');
            Route::post('gst/quick-store', [GstMasterController::class, 'quickStore'])->name('gst.quick-store');

            Route::get('packagings/download-template', [PackagingController::class, 'downloadTemplate'])->name('packagings.download-template');
            Route::post('packagings/import', [PackagingController::class, 'import'])->name('packagings.import');
            Route::get('packagings/trashed', [PackagingController::class, 'trashed'])->name('packagings.trashed');
            Route::put('packagings/{packaging}/restore', [PackagingController::class, 'restore'])->name('packagings.restore');
            Route::delete('packagings/{packaging}/force-delete', [PackagingController::class, 'forceDelete'])->name('packagings.force-delete');
            Route::post('packagings/{packaging}/toggle-status', [PackagingController::class, 'toggleStatus'])->name('packagings.toggle-status');
            Route::resource('packagings', PackagingController::class);
            Route::post('packagings/quick-store', [PackagingController::class, 'quickStore'])->name('packagings.quick-store');

            Route::get('units/download-template', [UnitController::class, 'downloadTemplate'])->name('units.download-template');
            Route::post('units/import', [UnitController::class, 'import'])->name('units.import');
            Route::get('units/trashed', [UnitController::class, 'trashed'])->name('units.trashed');
            Route::put('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
            Route::delete('units/{unit}/force-delete', [UnitController::class, 'forceDelete'])->name('units.force-delete');
            Route::post('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
            Route::resource('units', UnitController::class);
            Route::post('units/quick-store', [UnitController::class, 'quickStore'])->name('units.quick-store');

            Route::get('fuel-pumps/download-template', [FuelPumpController::class, 'downloadTemplate'])->name('fuel-pumps.download-template');
            Route::post('fuel-pumps/import', [FuelPumpController::class, 'import'])->name('fuel-pumps.import');
            Route::get('fuel-pumps/trashed', [FuelPumpController::class, 'trashed'])->name('fuel-pumps.trashed');
            Route::put('fuel-pumps/{fuel_pump}/restore', [FuelPumpController::class, 'restore'])->name('fuel-pumps.restore');
            Route::delete('fuel-pumps/{fuel_pump}/force-delete', [FuelPumpController::class, 'forceDelete'])->name('fuel-pumps.force-delete');
            Route::post('fuel-pumps/{fuel_pump}/toggle-status', [FuelPumpController::class, 'toggleStatus'])->name('fuel-pumps.toggle-status');
            Route::resource('fuel-pumps', FuelPumpController::class);
            Route::post('fuel-pumps/quick-store', [FuelPumpController::class, 'quickStore'])->name('fuel-pumps.quick-store');

            Route::get('fuel-companies/download-template', [FuelCompanyController::class, 'downloadTemplate'])->name('fuel-companies.download-template');
            Route::post('fuel-companies/import', [FuelCompanyController::class, 'import'])->name('fuel-companies.import');
            Route::get('fuel-companies/trashed', [FuelCompanyController::class, 'trashed'])->name('fuel-companies.trashed');
            Route::put('fuel-companies/{fuel_company}/restore', [FuelCompanyController::class, 'restore'])->name('fuel-companies.restore');
            Route::delete('fuel-companies/{fuel_company}/force-delete', [FuelCompanyController::class, 'forceDelete'])->name('fuel-companies.force-delete');
            Route::post('fuel-companies/{fuel_company}/toggle-status', [FuelCompanyController::class, 'toggleStatus'])->name('fuel-companies.toggle-status');
            Route::resource('fuel-companies', FuelCompanyController::class);

            Route::get('adblue-companies/download-template', [AdBlueCompanyController::class, 'downloadTemplate'])->name('adblue-companies.download-template');
            Route::post('adblue-companies/import', [AdBlueCompanyController::class, 'import'])->name('adblue-companies.import');
            Route::get('adblue-companies/trashed', [AdBlueCompanyController::class, 'trashed'])->name('adblue-companies.trashed');
            Route::put('adblue-companies/{adblue_company}/restore', [AdBlueCompanyController::class, 'restore'])->name('adblue-companies.restore');
            Route::delete('adblue-companies/{adblue_company}/force-delete', [AdBlueCompanyController::class, 'forceDelete'])->name('adblue-companies.force-delete');
            Route::post('adblue-companies/{adblue_company}/toggle-status', [AdBlueCompanyController::class, 'toggleStatus'])->name('adblue-companies.toggle-status');
            Route::resource('adblue-companies', AdBlueCompanyController::class);

            Route::get('tyre-brands/trashed', [\App\Http\Controllers\Admin\Masters\TyreBrandController::class, 'trashed'])->name('tyre-brands.trashed');
            Route::put('tyre-brands/{id}/restore', [\App\Http\Controllers\Admin\Masters\TyreBrandController::class, 'restore'])->name('tyre-brands.restore');
            Route::delete('tyre-brands/{id}/force-delete', [\App\Http\Controllers\Admin\Masters\TyreBrandController::class, 'forceDelete'])->name('tyre-brands.force-delete');
            Route::post('tyre-brands/{tyreBrand}/toggle-status', [\App\Http\Controllers\Admin\Masters\TyreBrandController::class, 'toggleStatus'])->name('tyre-brands.toggle-status');
            Route::post('tyre-brands/quick-store', [\App\Http\Controllers\Admin\Masters\TyreBrandController::class, 'quickStore'])->name('tyre-brands.quick-store');
            Route::resource('tyre-brands', \App\Http\Controllers\Admin\Masters\TyreBrandController::class);

            Route::get('tyre-models/trashed', [\App\Http\Controllers\Admin\Masters\TyreModelController::class, 'trashed'])->name('tyre-models.trashed');
            Route::put('tyre-models/{id}/restore', [\App\Http\Controllers\Admin\Masters\TyreModelController::class, 'restore'])->name('tyre-models.restore');
            Route::delete('tyre-models/{id}/force-delete', [\App\Http\Controllers\Admin\Masters\TyreModelController::class, 'forceDelete'])->name('tyre-models.force-delete');
            Route::post('tyre-models/{tyreModel}/toggle-status', [\App\Http\Controllers\Admin\Masters\TyreModelController::class, 'toggleStatus'])->name('tyre-models.toggle-status');
            Route::get('tyre-models/get-by-brand/{brandId}', [\App\Http\Controllers\Admin\Masters\TyreModelController::class, 'getByBrand'])->name('tyre-models.get-by-brand');
            Route::resource('tyre-models', \App\Http\Controllers\Admin\Masters\TyreModelController::class);

            Route::get('tyre-sizes/trashed', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'trashed'])->name('tyre-sizes.trashed');
            Route::put('tyre-sizes/{id}/restore', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'restore'])->name('tyre-sizes.restore');
            Route::delete('tyre-sizes/{id}/force-delete', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'forceDelete'])->name('tyre-sizes.force-delete');
            Route::post('tyre-sizes/{tyreSize}/toggle-status', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'toggleStatus'])->name('tyre-sizes.toggle-status');
            Route::get('tyre-sizes/get-by-model/{modelId}', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'getByModel'])->name('tyre-sizes.get-by-model');
            Route::get('tyre-sizes/get-by-brand/{brandId}', [\App\Http\Controllers\Admin\Masters\TyreSizeController::class, 'getByBrand'])->name('tyre-sizes.get-by-brand');
            Route::resource('tyre-sizes', \App\Http\Controllers\Admin\Masters\TyreSizeController::class);

            Route::get('items/download-template', [ItemController::class, 'downloadTemplate'])->name('items.download-template');
            Route::post('items/import', [ItemController::class, 'import'])->name('items.import');
            Route::get('items/trashed', [ItemController::class, 'trashed'])->name('items.trashed');
            Route::put('items/{item}/restore', [ItemController::class, 'restore'])->name('items.restore');
            Route::delete('items/{item}/force-delete', [ItemController::class, 'forceDelete'])->name('items.force-delete');
            Route::post('items/{item}/toggle-status', [ItemController::class, 'toggleStatus'])->name('items.toggle-status');
            Route::resource('items', ItemController::class);
            Route::get('items/search/autocomplete', [ItemController::class, 'search'])->name('items.search');
            Route::post('items/quick-store', [ItemController::class, 'quickStore'])->name('items.quick-store');

            Route::get('suppliers/trashed', [SupplierController::class, 'trashed'])->name('suppliers.trashed');
            Route::put('suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
            Route::delete('suppliers/{id}/force-delete', [SupplierController::class, 'forceDelete'])->name('suppliers.force-delete');
            Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
            Route::get('suppliers/download-template', [SupplierController::class, 'downloadTemplate'])->name('suppliers.download-template');
            Route::post('suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
            Route::resource('suppliers', SupplierController::class);

            Route::get('vendors/trashed', [VendorController::class, 'trashed'])->name('vendors.trashed');
            Route::put('vendors/{id}/restore', [VendorController::class, 'restore'])->name('vendors.restore');
            Route::delete('vendors/{id}/force-delete', [VendorController::class, 'forceDelete'])->name('vendors.force-delete');
            Route::post('vendors/{vendor}/toggle-status', [VendorController::class, 'toggleStatus'])->name('vendors.toggle-status');
            Route::get('vendors/download-template', [VendorController::class, 'downloadTemplate'])->name('vendors.download-template');
            Route::post('vendors/import', [VendorController::class, 'import'])->name('vendors.import');
            Route::resource('vendors', VendorController::class);

            Route::get('banks/download-template', [BankMasterController::class, 'downloadTemplate'])->name('banks.download-template');
            Route::post('banks/import', [BankMasterController::class, 'import'])->name('banks.import');
            Route::get('banks/trashed', [BankMasterController::class, 'trashed'])->name('banks.trashed');
            Route::put('banks/{bank}/restore', [BankMasterController::class, 'restore'])->name('banks.restore');
            Route::delete('banks/{bank}/force-delete', [BankMasterController::class, 'forceDelete'])->name('banks.force-delete');
            Route::post('banks/{bank}/toggle-status', [BankMasterController::class, 'toggleStatus'])->name('banks.toggle-status');
            Route::resource('banks', BankMasterController::class);

            Route::get('bank-branches/download-template', [BankBranchMasterController::class, 'downloadTemplate'])->name('bank-branches.download-template');
            Route::post('bank-branches/import', [BankBranchMasterController::class, 'import'])->name('bank-branches.import');
            Route::get('bank-branches/trashed', [BankBranchMasterController::class, 'trashed'])->name('bank-branches.trashed');
            Route::put('bank-branches/{bankBranch}/restore', [BankBranchMasterController::class, 'restore'])->name('bank-branches.restore');
            Route::delete('bank-branches/{bankBranch}/force-delete', [BankBranchMasterController::class, 'forceDelete'])->name('bank-branches.force-delete');
            Route::post('bank-branches/{bankBranch}/toggle-status', [BankBranchMasterController::class, 'toggleStatus'])->name('bank-branches.toggle-status');
            Route::resource('bank-branches', BankBranchMasterController::class);

            Route::name('bill-formats.')->prefix('bill-formats')->group(function () {
                Route::get('/', [BillFormatController::class, 'index'])->name('index');
                Route::get('create', [BillFormatController::class, 'create'])->name('create');
                Route::post('/', [BillFormatController::class, 'store'])->name('store');
                Route::get('{billFormat}/edit', [BillFormatController::class, 'edit'])->name('edit');
                Route::put('{billFormat}', [BillFormatController::class, 'update'])->name('update');
                Route::delete('{billFormat}', [BillFormatController::class, 'destroy'])->name('destroy');
                Route::get('get-depots', [BillFormatController::class, 'getDepots'])->name('get-depots');
                Route::get('get-parties', [BillFormatController::class, 'getParties'])->name('get-parties');
                Route::get('get-formats', [BillFormatController::class, 'getFormats'])->name('get-formats');
            });
        });

        Route::name('loan.')->prefix('loan')->group(function () {
            Route::get('company-loan/get-branches/{bankId}', [\App\Http\Controllers\Admin\CompanyLoanController::class, 'getBranches'])->name('company-loan.get-branches');
            Route::post('company-loan/{companyLoan}/toggle-status', [\App\Http\Controllers\Admin\CompanyLoanController::class, 'toggleStatus'])->name('company-loan.toggle-status');
            Route::post('company-loan/{companyLoan}/pay', [\App\Http\Controllers\Admin\CompanyLoanController::class, 'recordPayment'])->name('company-loan.pay');
            Route::get('company-loan/{companyLoan}/payments', [\App\Http\Controllers\Admin\CompanyLoanController::class, 'payments'])->name('company-loan.payments');
            Route::resource('company-loan', \App\Http\Controllers\Admin\CompanyLoanController::class);

            Route::get('vehicle', [\App\Http\Controllers\Admin\LoanController::class, 'vehicle'])->name('vehicle');
        });

        Route::name('maintenance.')->prefix('maintenance')->group(function () {
            Route::get('service-schedule/trashed', [\App\Http\Controllers\Admin\Maintenance\ServiceScheduleController::class, 'trashed'])->name('service-schedule.trashed');
            Route::put('service-schedule/{id}/restore', [\App\Http\Controllers\Admin\Maintenance\ServiceScheduleController::class, 'restore'])->name('service-schedule.restore');
            Route::delete('service-schedule/{id}/force-delete', [\App\Http\Controllers\Admin\Maintenance\ServiceScheduleController::class, 'forceDelete'])->name('service-schedule.force-delete');
            Route::post('service-schedule/{serviceSchedule}/mark-completed', [\App\Http\Controllers\Admin\Maintenance\ServiceScheduleController::class, 'markCompleted'])->name('service-schedule.mark-completed');
            Route::resource('service-schedule', \App\Http\Controllers\Admin\Maintenance\ServiceScheduleController::class);

            Route::get('spare-part/trashed', [\App\Http\Controllers\Admin\Maintenance\SparePartController::class, 'trashed'])->name('spare-part.trashed');
            Route::put('spare-part/{id}/restore', [\App\Http\Controllers\Admin\Maintenance\SparePartController::class, 'restore'])->name('spare-part.restore');
            Route::delete('spare-part/{id}/force-delete', [\App\Http\Controllers\Admin\Maintenance\SparePartController::class, 'forceDelete'])->name('spare-part.force-delete');
            Route::resource('spare-part', \App\Http\Controllers\Admin\Maintenance\SparePartController::class);

            Route::get('maintenance-history/trashed', [\App\Http\Controllers\Admin\Maintenance\MaintenanceHistoryController::class, 'trashed'])->name('maintenance-history.trashed');
            Route::put('maintenance-history/{id}/restore', [\App\Http\Controllers\Admin\Maintenance\MaintenanceHistoryController::class, 'restore'])->name('maintenance-history.restore');
            Route::delete('maintenance-history/{id}/force-delete', [\App\Http\Controllers\Admin\Maintenance\MaintenanceHistoryController::class, 'forceDelete'])->name('maintenance-history.force-delete');
            Route::resource('maintenance-history', \App\Http\Controllers\Admin\Maintenance\MaintenanceHistoryController::class);

            Route::get('breakdowns/trashed', [\App\Http\Controllers\Admin\Maintenance\BreakdownController::class, 'trashed'])->name('breakdowns.trashed');
            Route::put('breakdowns/{id}/restore', [\App\Http\Controllers\Admin\Maintenance\BreakdownController::class, 'restore'])->name('breakdowns.restore');
            Route::delete('breakdowns/{id}/force-delete', [\App\Http\Controllers\Admin\Maintenance\BreakdownController::class, 'forceDelete'])->name('breakdowns.force-delete');
            Route::post('breakdowns/{breakdown}/mark-resolved', [\App\Http\Controllers\Admin\Maintenance\BreakdownController::class, 'markResolved'])->name('breakdowns.mark-resolved');
            Route::resource('breakdowns', \App\Http\Controllers\Admin\Maintenance\BreakdownController::class);

            Route::get('tyre-management/layout', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'graphicLayout'])->name('tyre-management.layout');
            Route::post('tyre-management/update-position', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'updatePosition'])->name('tyre-management.update-position');
            Route::get('tyre-management/vehicle-tyres/{vehicle}', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'getVehicleTyres'])->name('tyre-management.vehicle-tyres');
            Route::get('tyre-management/trashed', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'trashed'])->name('tyre-management.trashed');
            Route::put('tyre-management/{id}/restore', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'restore'])->name('tyre-management.restore');
            Route::delete('tyre-management/{id}/force-delete', [\App\Http\Controllers\Admin\Maintenance\TyreManagementController::class, 'forceDelete'])->name('tyre-management.force-delete');
            Route::resource('tyre-management', \App\Http\Controllers\Admin\Maintenance\TyreManagementController::class);
        });
    });
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('run-migration', function () {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return response(Artisan::output());
        } catch (\Throwable $e) {
            return response('Migration failed: ' . $e->getMessage(), 500);
        }
    })->name('run.migration');

    Route::get('no', function () {
        try {
            Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder', '--force' => true]);
            $output = Artisan::output();
            return response("<pre style='background:#1e1e1e;color:#00ff00;padding:30px;font-family:monospace;border-radius:8px;'><b>Roles &amp; Permissions Seeder executed successfully!</b><br><br>" . ($output ?: 'All roles and permissions synced.') . "</pre>");
        } catch (\Throwable $e) {
            return response('Seeder failed: ' . $e->getMessage(), 500);
        }
    })->name('seed.roles-permissions');

    Route::get('seed-menu', function () {
        try {
            Artisan::call('db:seed', ['--class' => 'MenuSeeder', '--force' => true]);
            $output = Artisan::output();
            return response("<pre style='background:#1e1e1e;color:#00ff00;padding:30px;font-family:monospace;border-radius:8px;'><b>Menu Seeder executed successfully!</b><br><br>" . ($output ?: 'Menu structure seeded.') . "</pre>");
        } catch (\Throwable $e) {
            return response('Seeder failed: ' . $e->getMessage(), 500);
        }
    })->name('seed.menu');

    Route::get('seed-all', function () {
        try {
            Artisan::call('db:seed', ['--force' => true]);
            $output = Artisan::output();
            return response("<pre style='background:#1e1e1e;color:#00ff00;padding:30px;font-family:monospace;border-radius:8px;'><b>Full Database Seeder executed successfully!</b><br><br>" . ($output ?: 'All seeders completed.') . "</pre>");
        } catch (\Throwable $e) {
            return response('Seeder failed: ' . $e->getMessage(), 500);
        }
    })->name('seed.all');
});

