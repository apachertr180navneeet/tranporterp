<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'company_id',
        'branch_id',
        'consignor_id',
        'consignor_name',
        'company_name',
        'billing_address',
        'from_city_name',
        'to_city_name',
        'total_freight',
        'total_gst',
        'total_other',
        'invoice_no',
        'invoice_type',
        'bill_number',
        'template_type',
        'invoice_date',
        'total_amount',
        'amount_in_words',
        'visible_fields',
        'grn_fields',
        'grn_new_page',
        'status',
        'user_id',
        'gst_master_id',
        'tds',
        'deduction',
        'receiving_amount',
        'receiving_gst',
        'mn_number',
        'no_of_lrs',
        'custom_hsn_code',
        'custom_place_of_supply',
        'custom_district',
        'custom_state',
        'custom_state_code',
        'custom_gstn',
        'custom_pan_no',
        'state_vendor_code',
        'vendor_code',
        'vendor_name',
        'epod_status',
        'description',
        'custom_rate',
        'gst_type',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'rcm_payable',
    ];

    protected $casts = [
        'total_freight' => 'decimal:2',
        'total_gst' => 'decimal:2',
        'total_other' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'visible_fields' => 'array',
        'grn_fields' => 'array',
        'grn_new_page' => 'boolean',
        'rcm_payable' => 'boolean',
        'tds' => 'decimal:2',
        'deduction' => 'decimal:2',
        'receiving_amount' => 'decimal:2',
        'receiving_gst' => 'decimal:2',
        'custom_rate' => 'decimal:3',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function consignor()
    {
        return $this->belongsTo(Consignor::class, 'consignor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gstMaster()
    {
        return $this->belongsTo(GstMaster::class, 'gst_master_id');
    }

    public function bulties()
    {
        return $this->hasMany(Bulty::class, $this->invoice_type === 'toll' ? 'toll_invoice_id' : 'invoice_id');
    }

    public function freightBulties()
    {
        return $this->hasMany(Bulty::class, 'invoice_id');
    }

    public function tollBulties()
    {
        return $this->hasMany(Bulty::class, 'toll_invoice_id');
    }

    public function tollDetails()
    {
        return $this->hasMany(TollInvoiceDetail::class, 'toll_invoice_id');
    }

    public function billReceivings()
    {
        return $this->hasMany(BillReceiving::class, 'invoice_id');
    }

    public function getNetPayableAmountAttribute()
    {
        $amountWithoutGst = $this->total_freight + $this->total_other;
        return $amountWithoutGst + $this->total_gst - $this->tds - $this->deduction;
    }

    public function getTotalReceivedAmountAttribute()
    {
        return $this->receiving_amount + $this->receiving_gst;
    }

    public function getConsigneeNamesAttribute()
    {
        if ($this->invoice_type === 'toll') {
            $bulties = $this->relationLoaded('tollBulties') ? $this->tollBulties : $this->tollBulties()->with('consignee')->get();
        } else {
            $bulties = $this->relationLoaded('freightBulties') ? $this->freightBulties : $this->freightBulties()->with('consignee')->get();
        }

        if (!$bulties || $bulties->isEmpty()) {
            if ($this->relationLoaded('bulties') && $this->bulties->isNotEmpty()) {
                $bulties = $this->bulties;
            } else {
                return '-';
            }
        }

        $names = $bulties->pluck('consignee.name')->filter()->unique()->values();
        return $names->isNotEmpty() ? $names->implode(', ') : '-';
    }

    public function getOutstandingAmountAttribute()
    {
        return $this->net_payable_amount - $this->total_received_amount;
    }
}
