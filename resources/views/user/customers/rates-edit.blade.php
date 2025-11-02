@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">Giá khách: {{ $customer->name }}</h1>
    <a href="{{ route('user.customers.show',$customer) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
      Quay lại
    </a>
  </div>

  <form method="POST" action="{{ route('user.customers.rates.update',$customer) }}">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @foreach($regions as $key => $label)
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $label }}</h2>

        <div class="space-y-6">
          {{-- Nhóm: Giá đề (MB) --}}
          @if($key==='bac')
          <x-rate-card title="Giá đề">
            <x-rate-row code="de_dau" :region="$key" text="Đề đầu" :data="$data[$key]"/>
            <x-rate-row code="de_duoi" :region="$key" text="Đề đuôi (Đề GĐB)" :data="$data[$key]"/>
            <x-rate-row code="de_duoi_4" :region="$key" text="Đề đuôi 4 số" :data="$data[$key]" :digits="4"/>
          </x-rate-card>
          @endif

          {{-- Nhóm: Bao lô --}}
          <x-rate-card title="Giá bao lô">
            <x-rate-row code="bao_lo" :region="$key" text="Bao lô 2 số" :data="$data[$key]" :digits="2"/>
            <x-rate-row code="bao_lo" :region="$key" text="Bao lô 3 số" :data="$data[$key]" :digits="3"/>
            <x-rate-row code="bao_lo" :region="$key" text="Bao lô 4 số" :data="$data[$key]" :digits="4"/>
          </x-rate-card>

          {{-- Nhóm: Xiên đá --}}
          <x-rate-card title="Giá xiên đá">
            <x-rate-row code="da_thang" :region="$key" text="Đá thẳng (1 đài)" :data="$data[$key]" :dai="1"/>
            <x-rate-row code="da_xien"  :region="$key" text="Đá chéo (đá 2 đài)" :data="$data[$key]" :dai="2"/>
            <x-rate-row code="xien"     :region="$key" text="Xiên 2" :data="$data[$key]" :xien="2"/>
            <x-rate-row code="xien"     :region="$key" text="Xiên 3" :data="$data[$key]" :xien="3"/>
            <x-rate-row code="xien"     :region="$key" text="Xiên 4" :data="$data[$key]" :xien="4"/>
          </x-rate-card>

          {{-- Nhóm: Xỉu chủ --}}
          <x-rate-card title="Giá Xỉu chủ">
            <x-rate-row code="xiu_chu" :region="$key" text="Xỉu chủ" :data="$data[$key]"/>
          </x-rate-card>

          {{-- Nhóm: Bảy lô (MT/MN) --}}
          @if(in_array($key,['trung','nam']))
          <x-rate-card title="Giá Bảy lô (7 giải cuối)">
            <x-rate-row code="bay_lo" :region="$key" text="2 số" :data="$data[$key]" :digits="2"/>
            <x-rate-row code="bay_lo" :region="$key" text="3 số" :data="$data[$key]" :digits="3"/>
          </x-rate-card>
          @endif
        </div>
      </div>
      @endforeach
    </div>

    <div class="mt-6 flex justify-end">
      <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        Lưu thay đổi
      </button>
    </div>
  </form>
</div>
@endsection
