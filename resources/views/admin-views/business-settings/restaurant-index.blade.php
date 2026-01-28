@extends('layouts.admin.app')

@section('title', translate('business settings'))

@section('content')
    <div class="content container-fluid">
        @include('admin-views.business-settings.partial.business-settings-navmenu')

        <?php
        $config = \App\CentralLogics\Helpers::get_business_settings('maintenance_mode');
        $selectedMaintenanceSystem = \App\CentralLogics\Helpers::get_business_settings('maintenance_system_setup') ?? [];
        $selectedMaintenanceDuration = \App\CentralLogics\Helpers::get_business_settings('maintenance_duration_setup');
        $startDate = new DateTime($selectedMaintenanceDuration['start_date']);
        $endDate = new DateTime($selectedMaintenanceDuration['end_date']);
        ?>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="business-setting">
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="tio-notifications-alert mr-1"></i>
                            {{ translate('System Maintenance') }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                @if ($config)
                                    <div class="d-flex flex-wrap gap-3 align-items-center">
                                        <p class="mb-0">
                                            @if ($selectedMaintenanceDuration['maintenance_duration'] == 'until_change')
                                                {{ translate('Your maintenance mode is activated until I change') }}
                                            @else
                                                {{ translate('Your maintenance mode is activated from') }}<strong
                                                    class="pl-1">{{ $startDate->format('m/d/Y, h:i A') }}</strong>
                                                {{ translate('to') }}
                                                <strong>{{ $endDate->format('m/d/Y, h:i A') }}</strong>.
                                            @endif
                                            <a class="btn btn-outline-primary btn-sm py-1 px-2 edit square-btn maintenance-mode-show"
                                                href="#"><i class="tio-edit"></i></a>
                                        </p>
                                    </div>
                                @else
                                    <p>*{{ translate('By turning on maintenance mode Control your all system & function') }}
                                    </p>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <div
                                    class="d-flex justify-content-between align-items-center border rounded mb-2 px-3 py-2">
                                    <h5 class="mb-0">{{ translate('Maintenance Mode') }}</h5>
                                    <label class="toggle-switch toggle-switch-sm">
                                        <input type="checkbox"
                                            class="toggle-switch-input @if (!$config) maintenance-mode-show @endif"
                                            @if ($config) onclick="maintenance_mode()" @endif
                                            id="maintenance-mode-input" {{ $config ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text mb-0">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-8">
                                @if ($config && count($selectedMaintenanceSystem) > 0)
                                    <div class="d-flex flex-wrap gap-3 align-items-center">
                                        <h6 class="mb-0">
                                            {{ translate('Selected Systems') }}
                                        </h6>
                                        <ul
                                            class="selected-systems d-flex gap-4 flex-wrap bg-soft-dark px-5 py-1 mb-0 rounded">
                                            @foreach ($selectedMaintenanceSystem as $system)
                                                <li class="mr-5">{{ ucwords(str_replace('_', ' ', $system)) }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title d-flex align-items-center">
                            <span class="card-header-icon mb-1 mr-2">
                                <img src="{{ asset('public/assets/admin/img/bag.png') }}" class="w--17" alt="">
                            </span>
                            <span>{{ translate('Business Information') }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.business-settings.store.update-setup') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            @php($name = \App\Model\BusinessSetting::where('key', 'restaurant_name')->first()->value)
                            <div class="row">
                                @php($name = \App\Model\BusinessSetting::where('key', 'restaurant_name')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('restaurant') }}
                                            {{ translate('name') }}</label>
                                        <input type="text" name="restaurant_name" value="{{ $name }}"
                                            class="form-control" placeholder="{{ translate('New Restaurant') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="country">{{ translate('country') }}</label>
                                        <select id="country" name="country" class="form-control js-select2-custom">
                                            <option value="AF" @if (isset($savedCountry) && $savedCountry == 'AF') selected @endif>
                                                Afghanistan</option>
                                            <option value="AX" @if (isset($savedCountry) && $savedCountry == 'AX') selected @endif>Åland
                                                Islands</option>
                                            <option value="AL" @if (isset($savedCountry) && $savedCountry == 'AL') selected @endif>Albania
                                            </option>
                                            <option value="DZ" @if (isset($savedCountry) && $savedCountry == 'DZ') selected @endif>Algeria
                                            </option>
                                            <option value="AS" @if (isset($savedCountry) && $savedCountry == 'AS') selected @endif>
                                                American Samoa</option>
                                            <option value="AD" @if (isset($savedCountry) && $savedCountry == 'AD') selected @endif>
                                                Andorra</option>
                                            <option value="AO" @if (isset($savedCountry) && $savedCountry == 'AO') selected @endif>Angola
                                            </option>
                                            <option value="AI" @if (isset($savedCountry) && $savedCountry == 'AI') selected @endif>
                                                Anguilla</option>
                                            <option value="AQ" @if (isset($savedCountry) && $savedCountry == 'AQ') selected @endif>
                                                Antarctica</option>
                                            <option value="AG" @if (isset($savedCountry) && $savedCountry == 'AG') selected @endif>
                                                Antigua and Barbuda</option>
                                            <option value="AR" @if (isset($savedCountry) && $savedCountry == 'AR') selected @endif>
                                                Argentina</option>
                                            <option value="AM" @if (isset($savedCountry) && $savedCountry == 'AM') selected @endif>
                                                Armenia</option>
                                            <option value="AW" @if (isset($savedCountry) && $savedCountry == 'AW') selected @endif>Aruba
                                            </option>
                                            <option value="AU" @if (isset($savedCountry) && $savedCountry == 'AU') selected @endif>
                                                Australia</option>
                                            <option value="AT" @if (isset($savedCountry) && $savedCountry == 'AT') selected @endif>
                                                Austria</option>
                                            <option value="AZ" @if (isset($savedCountry) && $savedCountry == 'AZ') selected @endif>
                                                Azerbaijan</option>
                                            <option value="BS" @if (isset($savedCountry) && $savedCountry == 'BS') selected @endif>
                                                Bahamas</option>
                                            <option value="BH" @if (isset($savedCountry) && $savedCountry == 'BH') selected @endif>
                                                Bahrain</option>
                                            <option value="BD" @if (isset($savedCountry) && $savedCountry == 'BD') selected @endif>
                                                Bangladesh</option>
                                            <option value="BB" @if (isset($savedCountry) && $savedCountry == 'BB') selected @endif>
                                                Barbados</option>
                                            <option value="BY" @if (isset($savedCountry) && $savedCountry == 'BY') selected @endif>
                                                Belarus</option>
                                            <option value="BE" @if (isset($savedCountry) && $savedCountry == 'BE') selected @endif>
                                                Belgium</option>
                                            <option value="BZ" @if (isset($savedCountry) && $savedCountry == 'BZ') selected @endif>
                                                Belize</option>
                                            <option value="BJ" @if (isset($savedCountry) && $savedCountry == 'BJ') selected @endif>Benin
                                            </option>
                                            <option value="BM" @if (isset($savedCountry) && $savedCountry == 'BM') selected @endif>
                                                Bermuda</option>
                                            <option value="BT" @if (isset($savedCountry) && $savedCountry == 'BT') selected @endif>
                                                Bhutan</option>
                                            <option value="BO" @if (isset($savedCountry) && $savedCountry == 'BO') selected @endif>
                                                Bolivia, Plurinational State of</option>
                                            <option value="BQ" @if (isset($savedCountry) && $savedCountry == 'BQ') selected @endif>
                                                Bonaire, Sint Eustatius and Saba</option>
                                            <option value="BA" @if (isset($savedCountry) && $savedCountry == 'BA') selected @endif>
                                                Bosnia and Herzegovina</option>
                                            <option value="BW" @if (isset($savedCountry) && $savedCountry == 'BW') selected @endif>
                                                Botswana</option>
                                            <option value="BV" @if (isset($savedCountry) && $savedCountry == 'BV') selected @endif>
                                                Bouvet Island</option>
                                            <option value="BR" @if (isset($savedCountry) && $savedCountry == 'BR') selected @endif>
                                                Brazil</option>
                                            <option value="IO" @if (isset($savedCountry) && $savedCountry == 'IO') selected @endif>
                                                British Indian Ocean Territory</option>
                                            <option value="BN" @if (isset($savedCountry) && $savedCountry == 'BN') selected @endif>
                                                Brunei Darussalam</option>
                                            <option value="BG" @if (isset($savedCountry) && $savedCountry == 'BG') selected @endif>
                                                Bulgaria</option>
                                            <option value="BF" @if (isset($savedCountry) && $savedCountry == 'BF') selected @endif>
                                                Burkina Faso</option>
                                            <option value="BI" @if (isset($savedCountry) && $savedCountry == 'BI') selected @endif>
                                                Burundi</option>
                                            <option value="KH" @if (isset($savedCountry) && $savedCountry == 'KH') selected @endif>
                                                Cambodia</option>
                                            <option value="CM" @if (isset($savedCountry) && $savedCountry == 'CM') selected @endif>
                                                Cameroon</option>
                                            <option value="CA" @if (isset($savedCountry) && $savedCountry == 'CA') selected @endif>
                                                Canada</option>
                                            <option value="CV" @if (isset($savedCountry) && $savedCountry == 'CV') selected @endif>Cape
                                                Verde</option>
                                            <option value="KY" @if (isset($savedCountry) && $savedCountry == 'KY') selected @endif>
                                                Cayman Islands</option>
                                            <option value="CF" @if (isset($savedCountry) && $savedCountry == 'CF') selected @endif>
                                                Central African Republic</option>
                                            <option value="TD" @if (isset($savedCountry) && $savedCountry == 'TD') selected @endif>Chad
                                            </option>
                                            <option value="CL" @if (isset($savedCountry) && $savedCountry == 'CL') selected @endif>
                                                Chile</option>
                                            <option value="CN" @if (isset($savedCountry) && $savedCountry == 'CN') selected @endif>
                                                China</option>
                                            <option value="CX" @if (isset($savedCountry) && $savedCountry == 'CX') selected @endif>
                                                Christmas Island</option>
                                            <option value="CC" @if (isset($savedCountry) && $savedCountry == 'CC') selected @endif>
                                                Cocos (Keeling) Islands</option>
                                            <option value="CO" @if (isset($savedCountry) && $savedCountry == 'CO') selected @endif>
                                                Colombia</option>
                                            <option value="KM" @if (isset($savedCountry) && $savedCountry == 'KM') selected @endif>
                                                Comoros</option>
                                            <option value="CG" @if (isset($savedCountry) && $savedCountry == 'CG') selected @endif>
                                                Congo</option>
                                            <option value="CD" @if (isset($savedCountry) && $savedCountry == 'CD') selected @endif>
                                                Congo, the Democratic Republic of the</option>
                                            <option value="CK" @if (isset($savedCountry) && $savedCountry == 'CK') selected @endif>Cook
                                                Islands</option>
                                            <option value="CR" @if (isset($savedCountry) && $savedCountry == 'CR') selected @endif>
                                                Costa Rica</option>
                                            <option value="CI" @if (isset($savedCountry) && $savedCountry == 'CI') selected @endif>Côte
                                                d'Ivoire</option>
                                            <option value="HR" @if (isset($savedCountry) && $savedCountry == 'HR') selected @endif>
                                                Croatia</option>
                                            <option value="CU" @if (isset($savedCountry) && $savedCountry == 'CU') selected @endif>Cuba
                                            </option>
                                            <option value="CW" @if (isset($savedCountry) && $savedCountry == 'CW') selected @endif>
                                                Curaçao</option>
                                            <option value="CY" @if (isset($savedCountry) && $savedCountry == 'CY') selected @endif>
                                                Cyprus</option>
                                            <option value="CZ" @if (isset($savedCountry) && $savedCountry == 'CZ') selected @endif>
                                                Czech Republic</option>
                                            <option value="DK" @if (isset($savedCountry) && $savedCountry == 'DK') selected @endif>
                                                Denmark</option>
                                            <option value="DJ" @if (isset($savedCountry) && $savedCountry == 'DJ') selected @endif>
                                                Djibouti</option>
                                            <option value="DM" @if (isset($savedCountry) && $savedCountry == 'DM') selected @endif>
                                                Dominica</option>
                                            <option value="DO" @if (isset($savedCountry) && $savedCountry == 'DO') selected @endif>
                                                Dominican Republic</option>
                                            <option value="EC" @if (isset($savedCountry) && $savedCountry == 'EC') selected @endif>
                                                Ecuador</option>
                                            <option value="EG" @if (isset($savedCountry) && $savedCountry == 'EG') selected @endif>
                                                Egypt</option>
                                            <option value="SV" @if (isset($savedCountry) && $savedCountry == 'SV') selected @endif>El
                                                Salvador</option>
                                            <option value="GQ" @if (isset($savedCountry) && $savedCountry == 'GQ') selected @endif>
                                                Equatorial Guinea</option>
                                            <option value="ER" @if (isset($savedCountry) && $savedCountry == 'ER') selected @endif>
                                                Eritrea</option>
                                            <option value="EE" @if (isset($savedCountry) && $savedCountry == 'EE') selected @endif>
                                                Estonia</option>
                                            <option value="ET" @if (isset($savedCountry) && $savedCountry == 'ET') selected @endif>
                                                Ethiopia</option>
                                            <option value="FK" @if (isset($savedCountry) && $savedCountry == 'FK') selected @endif>
                                                Falkland Islands (Malvinas)</option>
                                            <option value="FO" @if (isset($savedCountry) && $savedCountry == 'FO') selected @endif>
                                                Faroe Islands</option>
                                            <option value="FJ" @if (isset($savedCountry) && $savedCountry == 'FJ') selected @endif>Fiji
                                            </option>
                                            <option value="FI" @if (isset($savedCountry) && $savedCountry == 'FI') selected @endif>
                                                Finland</option>
                                            <option value="FR" @if (isset($savedCountry) && $savedCountry == 'FR') selected @endif>
                                                France</option>
                                            <option value="GF" @if (isset($savedCountry) && $savedCountry == 'GF') selected @endif>
                                                French Guiana</option>
                                            <option value="PF" @if (isset($savedCountry) && $savedCountry == 'PF') selected @endif>
                                                French Polynesia</option>
                                            <option value="TF" @if (isset($savedCountry) && $savedCountry == 'TF') selected @endif>
                                                French Southern Territories</option>
                                            <option value="GA" @if (isset($savedCountry) && $savedCountry == 'GA') selected @endif>
                                                Gabon</option>
                                            <option value="GM" @if (isset($savedCountry) && $savedCountry == 'GM') selected @endif>
                                                Gambia</option>
                                            <option value="GE" @if (isset($savedCountry) && $savedCountry == 'GE') selected @endif>
                                                Georgia</option>
                                            <option value="DE" @if (isset($savedCountry) && $savedCountry == 'DE') selected @endif>
                                                Germany</option>
                                            <option value="GH" @if (isset($savedCountry) && $savedCountry == 'GH') selected @endif>
                                                Ghana</option>
                                            <option value="GI" @if (isset($savedCountry) && $savedCountry == 'GI') selected @endif>
                                                Gibraltar</option>
                                            <option value="GR" @if (isset($savedCountry) && $savedCountry == 'GR') selected @endif>
                                                Greece</option>
                                            <option value="GL" @if (isset($savedCountry) && $savedCountry == 'GL') selected @endif>
                                                Greenland</option>
                                            <option value="GD" @if (isset($savedCountry) && $savedCountry == 'GD') selected @endif>
                                                Grenada</option>
                                            <option value="GP" @if (isset($savedCountry) && $savedCountry == 'GP') selected @endif>
                                                Guadeloupe</option>
                                            <option value="GU" @if (isset($savedCountry) && $savedCountry == 'GU') selected @endif>Guam
                                            </option>
                                            <option value="GT" @if (isset($savedCountry) && $savedCountry == 'GT') selected @endif>
                                                Guatemala</option>
                                            <option value="GG" @if (isset($savedCountry) && $savedCountry == 'GG') selected @endif>
                                                Guernsey</option>
                                            <option value="GN" @if (isset($savedCountry) && $savedCountry == 'GN') selected @endif>
                                                Guinea</option>
                                            <option value="GW" @if (isset($savedCountry) && $savedCountry == 'GW') selected @endif>
                                                Guinea-Bissau</option>
                                            <option value="GY" @if (isset($savedCountry) && $savedCountry == 'GY') selected @endif>
                                                Guyana</option>
                                            <option value="HT" @if (isset($savedCountry) && $savedCountry == 'HT') selected @endif>
                                                Haiti</option>
                                            <option value="HM" @if (isset($savedCountry) && $savedCountry == 'HM') selected @endif>
                                                Heard Island and McDonald Islands</option>
                                            <option value="VA" @if (isset($savedCountry) && $savedCountry == 'VA') selected @endif>
                                                Holy See (Vatican City State)</option>
                                            <option value="HN" @if (isset($savedCountry) && $savedCountry == 'HN') selected @endif>
                                                Honduras</option>
                                            <option value="HK" @if (isset($savedCountry) && $savedCountry == 'HK') selected @endif>
                                                Hong Kong</option>
                                            <option value="HU" @if (isset($savedCountry) && $savedCountry == 'HU') selected @endif>
                                                Hungary</option>
                                            <option value="IS" @if (isset($savedCountry) && $savedCountry == 'IS') selected @endif>
                                                Iceland</option>
                                            <option value="IN" @if (isset($savedCountry) && $savedCountry == 'IN') selected @endif>
                                                India</option>
                                            <option value="ID" @if (isset($savedCountry) && $savedCountry == 'ID') selected @endif>
                                                Indonesia</option>
                                            <option value="IR" @if (isset($savedCountry) && $savedCountry == 'IR') selected @endif>
                                                Iran, Islamic Republic of</option>
                                            <option value="IQ" @if (isset($savedCountry) && $savedCountry == 'IQ') selected @endif>
                                                Iraq</option>
                                            <option value="IE" @if (isset($savedCountry) && $savedCountry == 'IE') selected @endif>
                                                Ireland</option>
                                            <option value="IM" @if (isset($savedCountry) && $savedCountry == 'IM') selected @endif>
                                                Isle of Man</option>
                                            <option value="IL" @if (isset($savedCountry) && $savedCountry == 'IL') selected @endif>
                                                Israel</option>
                                            <option value="IT" @if (isset($savedCountry) && $savedCountry == 'IT') selected @endif>
                                                Italy</option>
                                            <option value="JM" @if (isset($savedCountry) && $savedCountry == 'JM') selected @endif>
                                                Jamaica</option>
                                            <option value="JP" @if (isset($savedCountry) && $savedCountry == 'JP') selected @endif>
                                                Japan</option>
                                            <option value="JE" @if (isset($savedCountry) && $savedCountry == 'JE') selected @endif>
                                                Jersey</option>
                                            <option value="JO" @if (isset($savedCountry) && $savedCountry == 'JO') selected @endif>
                                                Jordan</option>
                                            <option value="KZ" @if (isset($savedCountry) && $savedCountry == 'KZ') selected @endif>
                                                Kazakhstan</option>
                                            <option value="KE" @if (isset($savedCountry) && $savedCountry == 'KE') selected @endif>
                                                Kenya</option>
                                            <option value="KI" @if (isset($savedCountry) && $savedCountry == 'KI') selected @endif>
                                                Kiribati</option>
                                            <option value="KP" @if (isset($savedCountry) && $savedCountry == 'KP') selected @endif>
                                                Korea, Democratic People's Republic of</option>
                                            <option value="KR" @if (isset($savedCountry) && $savedCountry == 'KR') selected @endif>
                                                Korea, Republic of</option>
                                            <option value="KW" @if (isset($savedCountry) && $savedCountry == 'KW') selected @endif>
                                                Kuwait</option>
                                            <option value="KG" @if (isset($savedCountry) && $savedCountry == 'KG') selected @endif>
                                                Kyrgyzstan</option>
                                            <option value="LA" @if (isset($savedCountry) && $savedCountry == 'LA') selected @endif>
                                                Lao People's Democratic Republic</option>
                                            <option value="LV" @if (isset($savedCountry) && $savedCountry == 'LV') selected @endif>
                                                Latvia</option>
                                            <option value="LB" @if (isset($savedCountry) && $savedCountry == 'LB') selected @endif>
                                                Lebanon</option>
                                            <option value="LS" @if (isset($savedCountry) && $savedCountry == 'LS') selected @endif>
                                                Lesotho</option>
                                            <option value="LR" @if (isset($savedCountry) && $savedCountry == 'LR') selected @endif>
                                                Liberia</option>
                                            <option value="LY" @if (isset($savedCountry) && $savedCountry == 'LY') selected @endif>
                                                Libya</option>
                                            <option value="LI" @if (isset($savedCountry) && $savedCountry == 'LI') selected @endif>
                                                Liechtenstein</option>
                                            <option value="LT" @if (isset($savedCountry) && $savedCountry == 'LT') selected @endif>
                                                Lithuania</option>
                                            <option value="LU" @if (isset($savedCountry) && $savedCountry == 'LU') selected @endif>
                                                Luxembourg</option>
                                            <option value="MO" @if (isset($savedCountry) && $savedCountry == 'MO') selected @endif>
                                                Macao</option>
                                            <option value="MK" @if (isset($savedCountry) && $savedCountry == 'MK') selected @endif>
                                                Macedonia, the former Yugoslav Republic of</option>
                                            <option value="MG" @if (isset($savedCountry) && $savedCountry == 'MG') selected @endif>
                                                Madagascar</option>
                                            <option value="MW" @if (isset($savedCountry) && $savedCountry == 'MW') selected @endif>
                                                Malawi</option>
                                            <option value="MY" @if (isset($savedCountry) && $savedCountry == 'MY') selected @endif>
                                                Malaysia</option>
                                            <option value="MV" @if (isset($savedCountry) && $savedCountry == 'MV') selected @endif>
                                                Maldives</option>
                                            <option value="ML" @if (isset($savedCountry) && $savedCountry == 'ML') selected @endif>
                                                Mali</option>
                                            <option value="MT" @if (isset($savedCountry) && $savedCountry == 'MT') selected @endif>
                                                Malta</option>
                                            <option value="MH" @if (isset($savedCountry) && $savedCountry == 'MH') selected @endif>
                                                Marshall Islands</option>
                                            <option value="MQ" @if (isset($savedCountry) && $savedCountry == 'MQ') selected @endif>
                                                Martinique</option>
                                            <option value="MR" @if (isset($savedCountry) && $savedCountry == 'MR') selected @endif>
                                                Mauritania</option>
                                            <option value="MU" @if (isset($savedCountry) && $savedCountry == 'MU') selected @endif>
                                                Mauritius</option>
                                            <option value="YT" @if (isset($savedCountry) && $savedCountry == 'YT') selected @endif>
                                                Mayotte</option>
                                            <option value="MX" @if (isset($savedCountry) && $savedCountry == 'MX') selected @endif>
                                                Mexico</option>
                                            <option value="FM" @if (isset($savedCountry) && $savedCountry == 'FM') selected @endif>
                                                Micronesia, Federated States of</option>
                                            <option value="MD" @if (isset($savedCountry) && $savedCountry == 'MD') selected @endif>
                                                Moldova, Republic of</option>
                                            <option value="MC" @if (isset($savedCountry) && $savedCountry == 'MC') selected @endif>
                                                Monaco</option>
                                            <option value="MN" @if (isset($savedCountry) && $savedCountry == 'MN') selected @endif>
                                                Mongolia</option>
                                            <option value="ME" @if (isset($savedCountry) && $savedCountry == 'ME') selected @endif>
                                                Montenegro</option>
                                            <option value="MS" @if (isset($savedCountry) && $savedCountry == 'MS') selected @endif>
                                                Montserrat</option>
                                            <option value="MA" @if (isset($savedCountry) && $savedCountry == 'MA') selected @endif>
                                                Morocco</option>
                                            <option value="MZ" @if (isset($savedCountry) && $savedCountry == 'MZ') selected @endif>
                                                Mozambique</option>
                                            <option value="MM" @if (isset($savedCountry) && $savedCountry == 'MM') selected @endif>
                                                Myanmar</option>
                                            <option value="NA" @if (isset($savedCountry) && $savedCountry == 'NA') selected @endif>
                                                Namibia</option>
                                            <option value="NR" @if (isset($savedCountry) && $savedCountry == 'NR') selected @endif>
                                                Nauru</option>
                                            <option value="NP" @if (isset($savedCountry) && $savedCountry == 'NP') selected @endif>
                                                Nepal</option>
                                            <option value="NL" @if (isset($savedCountry) && $savedCountry == 'NL') selected @endif>
                                                Netherlands</option>
                                            <option value="NC" @if (isset($savedCountry) && $savedCountry == 'NC') selected @endif>
                                                New Caledonia</option>
                                            <option value="NZ" @if (isset($savedCountry) && $savedCountry == 'NZ') selected @endif>
                                                New Zealand</option>
                                            <option value="NI" @if (isset($savedCountry) && $savedCountry == 'NI') selected @endif>
                                                Nicaragua</option>
                                            <option value="NE" @if (isset($savedCountry) && $savedCountry == 'NE') selected @endif>
                                                Niger</option>
                                            <option value="NG" @if (isset($savedCountry) && $savedCountry == 'NG') selected @endif>
                                                Nigeria</option>
                                            <option value="NU" @if (isset($savedCountry) && $savedCountry == 'NU') selected @endif>
                                                Niue</option>
                                            <option value="NF" @if (isset($savedCountry) && $savedCountry == 'NF') selected @endif>
                                                Norfolk Island</option>
                                            <option value="MP" @if (isset($savedCountry) && $savedCountry == 'MP') selected @endif>
                                                Northern Mariana Islands</option>
                                            <option value="NO" @if (isset($savedCountry) && $savedCountry == 'NO') selected @endif>
                                                Norway</option>
                                            <option value="OM" @if (isset($savedCountry) && $savedCountry == 'OM') selected @endif>
                                                Oman</option>
                                            <option value="PK" @if (isset($savedCountry) && $savedCountry == 'PK') selected @endif>
                                                Pakistan</option>
                                            <option value="PW" @if (isset($savedCountry) && $savedCountry == 'PW') selected @endif>
                                                Palau</option>
                                            <option value="PS" @if (isset($savedCountry) && $savedCountry == 'PS') selected @endif>
                                                Palestinian Territory, Occupied</option>
                                            <option value="PA" @if (isset($savedCountry) && $savedCountry == 'PA') selected @endif>
                                                Panama</option>
                                            <option value="PG" @if (isset($savedCountry) && $savedCountry == 'PG') selected @endif>
                                                Papua New Guinea</option>
                                            <option value="PY" @if (isset($savedCountry) && $savedCountry == 'PY') selected @endif>
                                                Paraguay</option>
                                            <option value="PE" @if (isset($savedCountry) && $savedCountry == 'PE') selected @endif>
                                                Peru</option>
                                            <option value="PH" @if (isset($savedCountry) && $savedCountry == 'PH') selected @endif>
                                                Philippines</option>
                                            <option value="PN" @if (isset($savedCountry) && $savedCountry == 'PN') selected @endif>
                                                Pitcairn</option>
                                            <option value="PL" @if (isset($savedCountry) && $savedCountry == 'PL') selected @endif>
                                                Poland</option>
                                            <option value="PT" @if (isset($savedCountry) && $savedCountry == 'PT') selected @endif>
                                                Portugal</option>
                                            <option value="PR" @if (isset($savedCountry) && $savedCountry == 'PR') selected @endif>
                                                Puerto Rico</option>
                                            <option value="QA" @if (isset($savedCountry) && $savedCountry == 'QA') selected @endif>
                                                Qatar</option>
                                            <option value="RE" @if (isset($savedCountry) && $savedCountry == 'RE') selected @endif>
                                                Réunion</option>
                                            <option value="RO" @if (isset($savedCountry) && $savedCountry == 'RO') selected @endif>
                                                Romania</option>
                                            <option value="RU" @if (isset($savedCountry) && $savedCountry == 'RU') selected @endif>
                                                Russian Federation</option>
                                            <option value="RW" @if (isset($savedCountry) && $savedCountry == 'RW') selected @endif>
                                                Rwanda</option>
                                            <option value="BL" @if (isset($savedCountry) && $savedCountry == 'BL') selected @endif>
                                                Saint Barthélemy</option>
                                            <option value="SH" @if (isset($savedCountry) && $savedCountry == 'SH') selected @endif>
                                                Saint Helena, Ascension and Tristan da Cunha</option>
                                            <option value="KN" @if (isset($savedCountry) && $savedCountry == 'KN') selected @endif>
                                                Saint Kitts and Nevis</option>
                                            <option value="LC" @if (isset($savedCountry) && $savedCountry == 'LC') selected @endif>
                                                Saint Lucia</option>
                                            <option value="MF" @if (isset($savedCountry) && $savedCountry == 'MF') selected @endif>
                                                Saint Martin (French part)</option>
                                            <option value="PM" @if (isset($savedCountry) && $savedCountry == 'PM') selected @endif>
                                                Saint Pierre and Miquelon</option>
                                            <option value="VC" @if (isset($savedCountry) && $savedCountry == 'VC') selected @endif>
                                                Saint Vincent and the Grenadines</option>
                                            <option value="WS" @if (isset($savedCountry) && $savedCountry == 'WS') selected @endif>
                                                Samoa</option>
                                            <option value="SM" @if (isset($savedCountry) && $savedCountry == 'SM') selected @endif>
                                                San Marino</option>
                                            <option value="ST" @if (isset($savedCountry) && $savedCountry == 'ST') selected @endif>
                                                Sao Tome and Principe</option>
                                            <option value="SA" @if (isset($savedCountry) && $savedCountry == 'SA') selected @endif>
                                                Saudi Arabia</option>
                                            <option value="SN" @if (isset($savedCountry) && $savedCountry == 'SN') selected @endif>
                                                Senegal</option>
                                            <option value="RS" @if (isset($savedCountry) && $savedCountry == 'RS') selected @endif>
                                                Serbia</option>
                                            <option value="SC" @if (isset($savedCountry) && $savedCountry == 'SC') selected @endif>
                                                Seychelles</option>
                                            <option value="SL" @if (isset($savedCountry) && $savedCountry == 'SL') selected @endif>
                                                Sierra Leone</option>
                                            <option value="SG" @if (isset($savedCountry) && $savedCountry == 'SG') selected @endif>
                                                Singapore</option>
                                            <option value="SX" @if (isset($savedCountry) && $savedCountry == 'SX') selected @endif>
                                                Sint Maarten (Dutch part)</option>
                                            <option value="SK" @if (isset($savedCountry) && $savedCountry == 'SK') selected @endif>
                                                Slovakia</option>
                                            <option value="SI" @if (isset($savedCountry) && $savedCountry == 'SI') selected @endif>
                                                Slovenia</option>
                                            <option value="SB" @if (isset($savedCountry) && $savedCountry == 'SB') selected @endif>
                                                Solomon Islands</option>
                                            <option value="SO" @if (isset($savedCountry) && $savedCountry == 'SO') selected @endif>
                                                Somalia</option>
                                            <option value="ZA" @if (isset($savedCountry) && $savedCountry == 'ZA') selected @endif>
                                                South Africa</option>
                                            <option value="GS" @if (isset($savedCountry) && $savedCountry == 'GS') selected @endif>
                                                South Georgia and the South Sandwich Islands</option>
                                            <option value="SS" @if (isset($savedCountry) && $savedCountry == 'SS') selected @endif>
                                                South Sudan</option>
                                            <option value="ES" @if (isset($savedCountry) && $savedCountry == 'ES') selected @endif>
                                                Spain</option>
                                            <option value="LK" @if (isset($savedCountry) && $savedCountry == 'LK') selected @endif>
                                                Sri Lanka</option>
                                            <option value="SD" @if (isset($savedCountry) && $savedCountry == 'SD') selected @endif>
                                                Sudan</option>
                                            <option value="SR" @if (isset($savedCountry) && $savedCountry == 'SR') selected @endif>
                                                Suriname</option>
                                            <option value="SJ" @if (isset($savedCountry) && $savedCountry == 'SJ') selected @endif>
                                                Svalbard and Jan Mayen</option>
                                            <option value="SZ" @if (isset($savedCountry) && $savedCountry == 'SZ') selected @endif>
                                                Swaziland</option>
                                            <option value="SE" @if (isset($savedCountry) && $savedCountry == 'SE') selected @endif>
                                                Sweden</option>
                                            <option value="CH" @if (isset($savedCountry) && $savedCountry == 'CH') selected @endif>
                                                Switzerland</option>
                                            <option value="SY" @if (isset($savedCountry) && $savedCountry == 'SY') selected @endif>
                                                Syrian Arab Republic</option>
                                            <option value="TW" @if (isset($savedCountry) && $savedCountry == 'TW') selected @endif>
                                                Taiwan, Province of China</option>
                                            <option value="TJ" @if (isset($savedCountry) && $savedCountry == 'TJ') selected @endif>
                                                Tajikistan</option>
                                            <option value="TZ" @if (isset($savedCountry) && $savedCountry == 'TZ') selected @endif>
                                                Tanzania, United Republic of</option>
                                            <option value="TH" @if (isset($savedCountry) && $savedCountry == 'TH') selected @endif>
                                                Thailand</option>
                                            <option value="TL" @if (isset($savedCountry) && $savedCountry == 'TL') selected @endif>
                                                Timor-Leste</option>
                                            <option value="TG" @if (isset($savedCountry) && $savedCountry == 'TG') selected @endif>
                                                Togo</option>
                                            <option value="TK" @if (isset($savedCountry) && $savedCountry == 'TK') selected @endif>
                                                Tokelau</option>
                                            <option value="TO" @if (isset($savedCountry) && $savedCountry == 'TO') selected @endif>
                                                Tonga</option>
                                            <option value="TT" @if (isset($savedCountry) && $savedCountry == 'TT') selected @endif>
                                                Trinidad and Tobago</option>
                                            <option value="TN" @if (isset($savedCountry) && $savedCountry == 'TN') selected @endif>
                                                Tunisia</option>
                                            <option value="TR" @if (isset($savedCountry) && $savedCountry == 'TR') selected @endif>
                                                Turkey</option>
                                            <option value="TM" @if (isset($savedCountry) && $savedCountry == 'TM') selected @endif>
                                                Turkmenistan</option>
                                            <option value="TC" @if (isset($savedCountry) && $savedCountry == 'TC') selected @endif>
                                                Turks and Caicos Islands</option>
                                            <option value="TV" @if (isset($savedCountry) && $savedCountry == 'TV') selected @endif>
                                                Tuvalu</option>
                                            <option value="UG" @if (isset($savedCountry) && $savedCountry == 'UG') selected @endif>
                                                Uganda</option>
                                            <option value="UA" @if (isset($savedCountry) && $savedCountry == 'UA') selected @endif>
                                                Ukraine</option>
                                            <option value="AE" @if (isset($savedCountry) && $savedCountry == 'AE') selected @endif>
                                                United Arab Emirates</option>
                                            <option value="GB" @if (isset($savedCountry) && $savedCountry == 'GB') selected @endif>
                                                United Kingdom</option>
                                            <option value="US" @if (isset($savedCountry) && $savedCountry == 'US') selected @endif>
                                                United States</option>
                                            <option value="UM" @if (isset($savedCountry) && $savedCountry == 'UM') selected @endif>
                                                United States Minor Outlying Islands</option>
                                            <option value="UY" @if (isset($savedCountry) && $savedCountry == 'UY') selected @endif>
                                                Uruguay</option>
                                            <option value="UZ" @if (isset($savedCountry) && $savedCountry == 'UZ') selected @endif>
                                                Uzbekistan</option>
                                            <option value="VU" @if (isset($savedCountry) && $savedCountry == 'VU') selected @endif>
                                                Vanuatu</option>
                                            <option value="VE" @if (isset($savedCountry) && $savedCountry == 'VE') selected @endif>
                                                Venezuela, Bolivarian Republic of</option>
                                            <option value="VN" @if (isset($savedCountry) && $savedCountry == 'VN') selected @endif>
                                                Viet Nam</option>
                                            <option value="VG" @if (isset($savedCountry) && $savedCountry == 'VG') selected @endif>
                                                Virgin Islands, British</option>
                                            <option value="VI" @if (isset($savedCountry) && $savedCountry == 'VI') selected @endif>
                                                Virgin Islands, U.S.</option>
                                            <option value="WF" @if (isset($savedCountry) && $savedCountry == 'WF') selected @endif>
                                                Wallis and Futuna</option>
                                            <option value="EH" @if (isset($savedCountry) && $savedCountry == 'EH') selected @endif>
                                                Western Sahara</option>
                                            <option value="YE" @if (isset($savedCountry) && $savedCountry == 'YE') selected @endif>
                                                Yemen</option>
                                            <option value="ZM" @if (isset($savedCountry) && $savedCountry == 'ZM') selected @endif>
                                                Zambia</option>
                                            <option value="ZW" @if (isset($savedCountry) && $savedCountry == 'ZW') selected @endif>
                                                Zimbabwe</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('time_zone') }}</label>
                                        <select name="time_zone" id="time_zone" data-maximum-selection-length="3"
                                            class="form-control js-select2-custom">
                                            <option value='Pacific/Midway'>(UTC-11:00) Midway Island</option>
                                            <option value='Pacific/Samoa'>(UTC-11:00) Samoa</option>
                                            <option value='Pacific/Honolulu'>(UTC-10:00) Hawaii</option>
                                            <option value='US/Alaska'>(UTC-09:00) Alaska</option>
                                            <option value='America/Los_Angeles'>(UTC-08:00) Pacific Time (US &amp; Canada)
                                            </option>
                                            <option value='America/Tijuana'>(UTC-08:00) Tijuana</option>
                                            <option value='US/Arizona'>(UTC-07:00) Arizona</option>
                                            <option value='America/Chihuahua'>(UTC-07:00) Chihuahua</option>
                                            <option value='America/Chihuahua'>(UTC-07:00) La Paz</option>
                                            <option value='America/Mazatlan'>(UTC-07:00) Mazatlan</option>
                                            <option value='US/Mountain'>(UTC-07:00) Mountain Time (US &amp; Canada)
                                            </option>
                                            <option value='America/Managua'>(UTC-06:00) Central America</option>
                                            <option value='US/Central'>(UTC-06:00) Central Time (US &amp; Canada)</option>
                                            <option value='America/Mexico_City'>(UTC-06:00) Guadalajara</option>
                                            <option value='America/Mexico_City'>(UTC-06:00) Mexico City</option>
                                            <option value='America/Monterrey'>(UTC-06:00) Monterrey</option>
                                            <option value='Canada/Saskatchewan'>(UTC-06:00) Saskatchewan</option>
                                            <option value='America/Bogota'>(UTC-05:00) Bogota</option>
                                            <option value='US/Eastern'>(UTC-05:00) Eastern Time (US &amp; Canada)</option>
                                            <option value='US/East-Indiana'>(UTC-05:00) Indiana (East)</option>
                                            <option value='America/Lima'>(UTC-05:00) Lima</option>
                                            <option value='America/Bogota'>(UTC-05:00) Quito</option>
                                            <option value='Canada/Atlantic'>(UTC-04:00) Atlantic Time (Canada)</option>
                                            <option value='America/Caracas'>(UTC-04:30) Caracas</option>
                                            <option value='America/La_Paz'>(UTC-04:00) La Paz</option>
                                            <option value='America/Santiago'>(UTC-04:00) Santiago</option>
                                            <option value='Canada/Newfoundland'>(UTC-03:30) Newfoundland</option>
                                            <option value='America/Sao_Paulo'>(UTC-03:00) Brasilia</option>
                                            <option value='America/Argentina/Buenos_Aires'>(UTC-03:00) Buenos Aires
                                            </option>
                                            <option value='America/Argentina/Buenos_Aires'>(UTC-03:00) Georgetown</option>
                                            <option value='America/Godthab'>(UTC-03:00) Greenland</option>
                                            <option value='America/Noronha'>(UTC-02:00) Mid-Atlantic</option>
                                            <option value='Atlantic/Azores'>(UTC-01:00) Azores</option>
                                            <option value='Atlantic/Cape_Verde'>(UTC-01:00) Cape Verde Is.</option>
                                            <option value='Africa/Casablanca'>(UTC+00:00) Casablanca</option>
                                            <option value='Europe/London'>(UTC+00:00) Edinburgh</option>
                                            <option value='Etc/Greenwich'>(UTC+00:00) Greenwich Mean Time : Dublin</option>
                                            <option value='Europe/Lisbon'>(UTC+00:00) Lisbon</option>
                                            <option value='Europe/London'>(UTC+00:00) London</option>
                                            <option value='Africa/Monrovia'>(UTC+00:00) Monrovia</option>
                                            <option value='UTC'>(UTC+00:00) UTC</option>
                                            <option value='Europe/Amsterdam'>(UTC+01:00) Amsterdam</option>
                                            <option value='Europe/Belgrade'>(UTC+01:00) Belgrade</option>
                                            <option value='Europe/Berlin'>(UTC+01:00) Berlin</option>
                                            <option value='Europe/Berlin'>(UTC+01:00) Bern</option>
                                            <option value='Europe/Bratislava'>(UTC+01:00) Bratislava</option>
                                            <option value='Europe/Brussels'>(UTC+01:00) Brussels</option>
                                            <option value='Europe/Budapest'>(UTC+01:00) Budapest</option>
                                            <option value='Europe/Copenhagen'>(UTC+01:00) Copenhagen</option>
                                            <option value='Europe/Ljubljana'>(UTC+01:00) Ljubljana</option>
                                            <option value='Europe/Madrid'>(UTC+01:00) Madrid</option>
                                            <option value='Europe/Paris'>(UTC+01:00) Paris</option>
                                            <option value='Europe/Prague'>(UTC+01:00) Prague</option>
                                            <option value='Europe/Rome'>(UTC+01:00) Rome</option>
                                            <option value='Europe/Sarajevo'>(UTC+01:00) Sarajevo</option>
                                            <option value='Europe/Skopje'>(UTC+01:00) Skopje</option>
                                            <option value='Europe/Stockholm'>(UTC+01:00) Stockholm</option>
                                            <option value='Europe/Vienna'>(UTC+01:00) Vienna</option>
                                            <option value='Europe/Warsaw'>(UTC+01:00) Warsaw</option>
                                            <option value='Africa/Lagos'>(UTC+01:00) West Central Africa</option>
                                            <option value='Europe/Zagreb'>(UTC+01:00) Zagreb</option>
                                            <option value='Europe/Athens'>(UTC+02:00) Athens</option>
                                            <option value='Europe/Bucharest'>(UTC+02:00) Bucharest</option>
                                            <option value='Africa/Cairo'>(UTC+02:00) Cairo</option>
                                            <option value='Africa/Harare'>(UTC+02:00) Harare</option>
                                            <option value='Europe/Helsinki'>(UTC+02:00) Helsinki</option>
                                            <option value='Europe/Istanbul'>(UTC+02:00) Istanbul</option>
                                            <option value='Asia/Jerusalem'>(UTC+02:00) Jerusalem</option>
                                            <option value='Europe/Helsinki'>(UTC+02:00) Kyiv</option>
                                            <option value='Africa/Johannesburg'>(UTC+02:00) Pretoria</option>
                                            <option value='Europe/Riga'>(UTC+02:00) Riga</option>
                                            <option value='Europe/Sofia'>(UTC+02:00) Sofia</option>
                                            <option value='Europe/Tallinn'>(UTC+02:00) Tallinn</option>
                                            <option value='Europe/Vilnius'>(UTC+02:00) Vilnius</option>
                                            <option value='Asia/Baghdad'>(UTC+03:00) Baghdad</option>
                                            <option value='Asia/Kuwait'>(UTC+03:00) Kuwait</option>
                                            <option value='Europe/Minsk'>(UTC+03:00) Minsk</option>
                                            <option value='Africa/Nairobi'>(UTC+03:00) Nairobi</option>
                                            <option value='Asia/Riyadh'>(UTC+03:00) Riyadh</option>
                                            <option value='Europe/Volgograd'>(UTC+03:00) Volgograd</option>
                                            <option value='Asia/Tehran'>(UTC+03:30) Tehran</option>
                                            <option value='Asia/Muscat'>(UTC+04:00) Abu Dhabi</option>
                                            <option value='Asia/Baku'>(UTC+04:00) Baku</option>
                                            <option value='Europe/Moscow'>(UTC+04:00) Moscow</option>
                                            <option value='Asia/Muscat'>(UTC+04:00) Muscat</option>
                                            <option value='Europe/Moscow'>(UTC+04:00) St. Petersburg</option>
                                            <option value='Asia/Tbilisi'>(UTC+04:00) Tbilisi</option>
                                            <option value='Asia/Yerevan'>(UTC+04:00) Yerevan</option>
                                            <option value='Asia/Kabul'>(UTC+04:30) Kabul</option>
                                            <option value='Asia/Karachi'>(UTC+05:00) Islamabad</option>
                                            <option value='Asia/Karachi'>(UTC+05:00) Karachi</option>
                                            <option value='Asia/Tashkent'>(UTC+05:00) Tashkent</option>
                                            <option value='Asia/Calcutta'>(UTC+05:30) Chennai</option>
                                            <option value='Asia/Kolkata'>(UTC+05:30) Kolkata</option>
                                            <option value='Asia/Calcutta'>(UTC+05:30) Mumbai</option>
                                            <option value='Asia/Calcutta'>(UTC+05:30) New Delhi</option>
                                            <option value='Asia/Calcutta'>(UTC+05:30) Sri Jayawardenepura</option>
                                            <option value='Asia/Katmandu'>(UTC+05:45) Kathmandu</option>
                                            <option value='Asia/Almaty'>(UTC+06:00) Almaty</option>
                                            <option value='Asia/Dhaka'>(UTC+06:00) Dhaka</option>
                                            <option value='Asia/Yekaterinburg'>(UTC+06:00) Ekaterinburg</option>
                                            <option value='Asia/Rangoon'>(UTC+06:30) Rangoon</option>
                                            <option value='Asia/Bangkok'>(UTC+07:00) Bangkok</option>
                                            <option value='Asia/Bangkok'>(UTC+07:00) Hanoi</option>
                                            <option value='Asia/Jakarta'>(UTC+07:00) Jakarta</option>
                                            <option value='Asia/Novosibirsk'>(UTC+07:00) Novosibirsk</option>
                                            <option value='Asia/Hong_Kong'>(UTC+08:00) Beijing</option>
                                            <option value='Asia/Chongqing'>(UTC+08:00) Chongqing</option>
                                            <option value='Asia/Hong_Kong'>(UTC+08:00) Hong Kong</option>
                                            <option value='Asia/Krasnoyarsk'>(UTC+08:00) Krasnoyarsk</option>
                                            <option value='Asia/Kuala_Lumpur'>(UTC+08:00) Kuala Lumpur</option>
                                            <option value='Australia/Perth'>(UTC+08:00) Perth</option>
                                            <option value='Asia/Singapore'>(UTC+08:00) Singapore</option>
                                            <option value='Asia/Taipei'>(UTC+08:00) Taipei</option>
                                            <option value='Asia/Ulan_Bator'>(UTC+08:00) Ulaan Bataar</option>
                                            <option value='Asia/Urumqi'>(UTC+08:00) Urumqi</option>
                                            <option value='Asia/Irkutsk'>(UTC+09:00) Irkutsk</option>
                                            <option value='Asia/Tokyo'>(UTC+09:00) Osaka</option>
                                            <option value='Asia/Tokyo'>(UTC+09:00) Sapporo</option>
                                            <option value='Asia/Seoul'>(UTC+09:00) Seoul</option>
                                            <option value='Asia/Tokyo'>(UTC+09:00) Tokyo</option>
                                            <option value='Australia/Adelaide'>(UTC+09:30) Adelaide</option>
                                            <option value='Australia/Darwin'>(UTC+09:30) Darwin</option>
                                            <option value='Australia/Brisbane'>(UTC+10:00) Brisbane</option>
                                            <option value='Australia/Canberra'>(UTC+10:00) Canberra</option>
                                            <option value='Pacific/Guam'>(UTC+10:00) Guam</option>
                                            <option value='Australia/Hobart'>(UTC+10:00) Hobart</option>
                                            <option value='Australia/Melbourne'>(UTC+10:00) Melbourne</option>
                                            <option value='Pacific/Port_Moresby'>(UTC+10:00) Port Moresby</option>
                                            <option value='Australia/Sydney'>(UTC+10:00) Sydney</option>
                                            <option value='Asia/Yakutsk'>(UTC+10:00) Yakutsk</option>
                                            <option value='Asia/Vladivostok'>(UTC+11:00) Vladivostok</option>
                                            <option value='Pacific/Auckland'>(UTC+12:00) Auckland</option>
                                            <option value='Pacific/Fiji'>(UTC+12:00) Fiji</option>
                                            <option value='Pacific/Kwajalein'>(UTC+12:00) International Date Line West
                                            </option>
                                            <option value='Asia/Kamchatka'>(UTC+12:00) Kamchatka</option>
                                            <option value='Asia/Magadan'>(UTC+12:00) Magadan</option>
                                            <option value='Pacific/Fiji'>(UTC+12:00) Marshall Is.</option>
                                            <option value='Asia/Magadan'>(UTC+12:00) New Caledonia</option>
                                            <option value='Asia/Magadan'>(UTC+12:00) Solomon Is.</option>
                                            <option value='Pacific/Auckland'>(UTC+12:00) Wellington</option>
                                            <option value='Pacific/Tongatapu'>(UTC+13:00) Nuku'alofa</option>

                                        </select>
                                    </div>
                                </div>

                                @php($phone = \App\Model\BusinessSetting::where('key', 'phone')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('phone') }}</label>
                                        <input type="text" value="{{ $phone }}" name="phone"
                                            class="form-control" placeholder="" required>
                                    </div>
                                </div>
                                @php($email = \App\Model\BusinessSetting::where('key', 'email_address')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('email') }}</label>
                                        <input type="email" value="{{ $email }}" name="email"
                                            class="form-control" placeholder="" required>
                                    </div>
                                </div>
                                @php($address = \App\Model\BusinessSetting::where('key', 'address')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('address') }}</label>
                                        <input type="text" value="{{ $address }}" name="address"
                                            class="form-control" placeholder="" required>
                                    </div>
                                </div>

                                @php($currency_code = \App\Model\BusinessSetting::where('key', 'currency')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('currency') }}</label>
                                        <select name="currency" class="form-control js-select2-custom">
                                            @foreach (\App\Model\Currency::orderBy('currency_code')->get() as $currency)
                                                <option value="{{ $currency['currency_code'] }}"
                                                    {{ $currency_code == $currency['currency_code'] ? 'selected' : '' }}>
                                                    {{ $currency['currency_code'] }} ( {{ $currency['currency_symbol'] }}
                                                    )
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @php($config = \App\CentralLogics\Helpers::get_business_settings('currency_symbol_position'))
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label">{{ translate('Currency Symbol Position') }}</label>

                                        <div class="d-flex flex-wrap align-items-center form-control border">
                                            <label
                                                class="form-check form--check mr-2 mr-md-4 mb-0 change-currency-position"
                                                data-route="{{ route('admin.business-settings.store.currency-position', ['left']) }}">
                                                <input type="radio" class="form-check-input"
                                                    name="projectViewNewProjectTypeRadio"
                                                    id="projectViewNewProjectTypeRadio1"
                                                    {{ isset($config) && $config == 'left' ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                    {{ translate('Left') }}
                                                </span>
                                            </label>
                                            <label class="form-check form--check mb-0 change-currency-position"
                                                data-route="{{ route('admin.business-settings.store.currency-position', ['right']) }}">
                                                <input type="radio" class="form-check-input"
                                                    name="projectViewNewProjectTypeRadio"
                                                    id="projectViewNewProjectTypeRadio2"
                                                    {{ isset($config) && $config == 'right' ? 'checked' : '' }}>
                                                <span class="form-check-label">
                                                    {{ translate('Right') }}
                                                    ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @php($decimal_point_settings = \App\CentralLogics\Helpers::get_business_settings('decimal_point_settings'))
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label
                                            class="input-label text-capitalize">{{ translate('digit_after_decimal_point') }}({{ translate(' ex: 0.00') }})</label>
                                        <input type="number" value="{{ $decimal_point_settings }}"
                                            name="decimal_point_settings" class="form-control" placeholder="" required>
                                    </div>
                                </div>

                                @php($footer_text = \App\Model\BusinessSetting::where('key', 'footer_text')->first()->value)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('footer') }}
                                            {{ translate('text') }}</label>
                                        <input type="text" value="{{ $footer_text }}" name="footer_text"
                                            class="form-control" placeholder="" required>
                                    </div>
                                </div>
                                @php($pagination_limit = \App\Model\BusinessSetting::where('key', 'pagination_limit')->first()->value)
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('pagination') }}</label>
                                        <input type="text" value="{{ $pagination_limit }}" name="pagination_limit"
                                            class="form-control" placeholder="" required>
                                    </div>
                                </div>
                                @php($time_format = \App\CentralLogics\Helpers::get_business_settings('time_format') ?? '24')
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label class="input-label text-capitalize">{{ translate('time_format') }}</label>
                                        <select name="time_format" class="form-control">
                                            <option value="12" {{ $time_format == '12' ? 'selected' : '' }}>
                                                {{ translate('12_hour') }}</option>
                                            <option value="24" {{ $time_format == '24' ? 'selected' : '' }}>
                                                {{ translate('24_hour') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <?php
                                    $sp = \App\Model\BusinessSetting::where('key', 'self_pickup')->first()->value;
                                    $status = $sp == 1 ? 0 : 1;
                                    ?>
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control change-self-pickup-status"
                                            data-route="{{ route('admin.business-settings.store.self-pickup', [$status]) }}">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('self_pickup') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('When this option is enabled the user may pick up their own order.') }}"><img
                                                        src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="self_pickup" class="toggle-switch-input"
                                                {{ $sp == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <?php
                                    $dm_self_registration = \App\Model\BusinessSetting::where('key', 'dm_self_registration')->first()->value;
                                    $dm_status = $dm_self_registration == 1 ? 0 : 1;
                                    ?>
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control deliveryman-self-registration"
                                            data-route="{{ route('admin.business-settings.store.dm-self-registration', [$dm_status]) }}">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('Deliverman_self_registration') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('When this field is active, delivery men can register themself using the delivery man app.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="dm_self_registration"
                                                class="toggle-switch-input"
                                                {{ $dm_self_registration == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    @php($guest_checkout = \App\CentralLogics\Helpers::get_business_settings('guest_checkout'))
                                    @php($guest_checkout_status = $guest_checkout == 1 ? 0 : 1)
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control guest-checkout-status"
                                            data-route="{{ route('admin.business-settings.store.guest-checkout', [$guest_checkout_status]) }}">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('guest_checkout') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('When this option is active, users may place orders as guests without logging in.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="guest_checkout" class="toggle-switch-input"
                                                {{ $guest_checkout == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-5">
                                    @php($partial_payment = \App\CentralLogics\Helpers::get_business_settings('partial_payment'))
                                    @php($partial_payment_status = $partial_payment == 1 ? 0 : 1)
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control"
                                            onclick="partial_payment_status('{{ route('admin.business-settings.store.partial-payment', [$partial_payment_status]) }}')">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('partial_payment') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('When this option is enabled, users may pay up to a certain amount using their wallet balance.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="partial_payment" class="toggle-switch-input"
                                                {{ $partial_payment == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                @php($combine_with = \App\CentralLogics\Helpers::get_business_settings('partial_payment_combine_with'))
                                <div class="col-md-4 col-12">
                                    <div class="form-group">
                                        <label
                                            class="input-label text-capitalize">{{ translate('partial_payment_combine_with') }}
                                            <span class="form-label-secondary text-danger ml-1" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('The wallet balance will be combined with the chosen payment method to complete the transaction.') }}">
                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                    alt="info">
                                            </span>
                                        </label>
                                        <select name="partial_payment_combine_with" class="form-control">
                                            <option value="COD" {{ $combine_with == 'COD' ? 'selected' : '' }}>
                                                {{ translate('COD') }}</option>
                                            <option value="digital" {{ $combine_with == 'digital' ? 'selected' : '' }}>
                                                {{ translate('digital') }}</option>
                                            <option value="offline" {{ $combine_with == 'offline' ? 'selected' : '' }}>
                                                {{ translate('offline') }}</option>
                                            <option value="all" {{ $combine_with == 'all' ? 'selected' : '' }}>
                                                {{ translate('all') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4 mt-5">
                                    @php($google_map = \App\CentralLogics\Helpers::get_business_settings('google_map_status'))
                                    @php($google_map_status = $google_map == 1 ? 0 : 1)
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('Google Map Status') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('When this option is enabled, google map will show all over system.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="google_map_status" id="google_map_status"
                                                class="toggle-switch-input" {{ $google_map == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-5">
                                    @php($order_notification = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification'))
                                    @php($order_notification_status = $order_notification == 1 ? 0 : 1)
                                    <div class="form-group">
                                        <label
                                            class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                                            <span class="pr-1 d-flex align-items-center switch--label">
                                                <span class="line--limit-1">
                                                    <strong>{{ translate('Order Notification') }}</strong>
                                                </span>
                                                <span class="form-label-secondary text-danger d-flex ml-1"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('Admin/Branch will get a pop-up notification with sounds for every order placed by customers.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </span>
                                            <input type="checkbox" name="admin_order_notification"
                                                class="toggle-switch-input" id="admin_order_notification"
                                                {{ $order_notification == 1 ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                @php($admin_order_notification_type = \App\CentralLogics\Helpers::get_business_settings('admin_order_notification_type'))
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label">{{ translate('Order Notification Type') }}
                                                <span class="form-label-secondary ml-1" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('For Firebase a single real-time notification will be sent upon order placement with no repetition. For the Manual option notifications will appear at 10-second intervals until the order is viewed.') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="info">
                                                </span>
                                            </label>
                                            <a class="pr-1 text-decoration-underline"
                                                href="{{ route('admin.business-settings.web-app.third-party.fcm-config') }}"
                                                target="_blank">{{ translate('Configure from here') }}</a>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center form-control border">
                                            <label class="form-check form--check mr-2 mr-md-4 mb-0">
                                                <input type="radio" class="form-check-input"
                                                    name="admin_order_notification_type" value="manual"
                                                    id="admin_order_notification_type1"
                                                    {{ isset($admin_order_notification_type) && $admin_order_notification_type == 'manual' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ translate('Manual') }}</span>
                                            </label>
                                            <label class="form-check form--check mb-0">
                                                <input type="radio" class="form-check-input"
                                                    name="admin_order_notification_type" value="firebase"
                                                    id="admin_order_notification_type2"
                                                    {{ isset($admin_order_notification_type) && $admin_order_notification_type == 'firebase' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ translate('Firebase') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label>{{ translate('logo') }}</label><small class="text-danger"> (
                                            {{ translate('ratio') }} 3:1 )</small>
                                        <div class="custom-file">
                                            <input type="file" name="logo" id="customFileEg1"
                                                class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label"
                                                for="customFileEg1">{{ translate('choose') }}
                                                {{ translate('file') }}</label>
                                        </div>
                                        <div class="text-center">
                                            <img id="viewer" class="mt-4 border rounded mw-100 p-2"
                                                src="{{ $logo }}" alt="{{ translate('logo') }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label>{{ translate('Fav Icon') }}</label><small class="text-danger"> (
                                            {{ translate('ratio') }} 1:1 )</small>
                                        <div class="custom-file">
                                            <input type="file" name="fav_icon" id="customFileEg2"
                                                class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label"
                                                for="customFileEg2">{{ translate('choose') }}
                                                {{ translate('file') }}</label>
                                        </div>
                                        <div class="text-center">
                                            <img id="viewer_2"
                                                class="mt-4 border rounded p-2 aspect-1 mw-145 object-cover"
                                                src="{{ $favIcon }}" alt="{{ translate('fav_icon') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="btn--container justify-content-end mt-5">
                                <button type="reset" class="btn btn--reset">{{ translate('reset') }}</button>
                                <button type="{{ env('APP_MODE') != 'demo' ? 'submit' : 'button' }}"
                                    class="btn btn--primary call-demo">{{ translate('save') }}</button>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="maintenance-mode-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="mb-0">
                        <i class="tio-notifications-alert mr-1"></i>
                        {{ translate('System Maintenance') }}
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="{{ route('admin.business-settings.store.maintenance-mode-setup') }}"
                    id="maintenanceModeForm">
                    <?php
                    $selectedMaintenanceSystem = \App\CentralLogics\Helpers::get_business_settings('maintenance_system_setup');
                    $selectedMaintenanceDuration = \App\CentralLogics\Helpers::get_business_settings('maintenance_duration_setup');
                    $selectedMaintenanceMessage = \App\CentralLogics\Helpers::get_business_settings('maintenance_message_setup');
                    $maintenanceMode = \App\CentralLogics\Helpers::get_business_settings('maintenance_mode') ?? 0;
                    ?>
                    <div class="modal-body">
                        @csrf
                        <div class="d-flex flex-column g-2">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <p>*{{ translate('By turning on maintenance mode Control your all system & function') }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <div
                                        class="d-flex justify-content-between align-items-center border rounded mb-2 px-3 py-2">
                                        <h5 class="mb-0">{{ translate('Maintenance Mode') }}</h5>
                                        <label class="toggle-switch toggle-switch-sm">
                                            <input type="checkbox" class="toggle-switch-input" name="maintenance_mode"
                                                id="maintenance-mode-checkbox" {{ $maintenanceMode ? 'checked' : '' }}>
                                            <span class="toggle-switch-label text mb-0">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-4">
                                    <h3>{{ translate('Select System') }}</h3>
                                    <p>{{ translate('Select the systems you want to temporarily deactivate for maintenance') }}
                                    </p>
                                </div>
                                <div class="col-xl-8">
                                    <div class="border p-3">
                                        <div class="d-flex flex-wrap g-3">
                                            <div class="form-check">
                                                <input class="form-check-input system-checkbox" name="all_system"
                                                    type="checkbox"
                                                    {{ in_array('branch_panel', $selectedMaintenanceSystem) &&
                                                    in_array('customer_app', $selectedMaintenanceSystem) &&
                                                    in_array('web_app', $selectedMaintenanceSystem) &&
                                                    in_array('deliveryman_app', $selectedMaintenanceSystem)
                                                        ? 'checked'
                                                        : '' }}
                                                    id="allSystem">
                                                <label class="form-check-label"
                                                    for="allSystem">{{ translate('All System') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input system-checkbox" name="branch_panel"
                                                    type="checkbox"
                                                    {{ in_array('branch_panel', $selectedMaintenanceSystem) ? 'checked' : '' }}
                                                    id="branchPanel">
                                                <label class="form-check-label"
                                                    for="branchPanel">{{ translate('Branch Panel') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input system-checkbox" name="customer_app"
                                                    type="checkbox"
                                                    {{ in_array('customer_app', $selectedMaintenanceSystem) ? 'checked' : '' }}
                                                    id="customerApp">
                                                <label class="form-check-label"
                                                    for="customerApp">{{ translate('Customer App') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input system-checkbox" name="web_app"
                                                    type="checkbox"
                                                    {{ in_array('web_app', $selectedMaintenanceSystem) ? 'checked' : '' }}
                                                    id="webApp">
                                                <label class="form-check-label"
                                                    for="webApp">{{ translate('Web App') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input system-checkbox" name="deliveryman_app"
                                                    type="checkbox"
                                                    {{ in_array('deliveryman_app', $selectedMaintenanceSystem) ? 'checked' : '' }}
                                                    id="deliverymanApp">
                                                <label class="form-check-label"
                                                    for="deliverymanApp">{{ translate('Deliveryman App') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-4">
                                    <h3>{{ translate('Maintenance Date') }} & {{ translate('Time') }}</h3>
                                    <p>{{ translate('Choose the maintenance mode duration for your selected system.') }}
                                    </p>
                                </div>
                                <div class="col-xl-8">
                                    <div class="border p-3">
                                        <div class="d-flex flex-wrap g-3 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                    name="maintenance_duration"
                                                    {{ isset($selectedMaintenanceDuration['maintenance_duration']) && $selectedMaintenanceDuration['maintenance_duration'] == 'one_day' ? 'checked' : '' }}
                                                    value="one_day" id="one_day">
                                                <label class="form-check-label"
                                                    for="one_day">{{ translate('For 24 Hours') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                    name="maintenance_duration"
                                                    {{ isset($selectedMaintenanceDuration['maintenance_duration']) && $selectedMaintenanceDuration['maintenance_duration'] == 'one_week' ? 'checked' : '' }}
                                                    value="one_week" id="one_week">
                                                <label class="form-check-label"
                                                    for="one_week">{{ translate('For 1 Week') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                    name="maintenance_duration"
                                                    {{ isset($selectedMaintenanceDuration['maintenance_duration']) && $selectedMaintenanceDuration['maintenance_duration'] == 'until_change' ? 'checked' : '' }}
                                                    value="until_change" id="until_change">
                                                <label class="form-check-label"
                                                    for="until_change">{{ translate('Until I change') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                    name="maintenance_duration"
                                                    {{ isset($selectedMaintenanceDuration['maintenance_duration']) && $selectedMaintenanceDuration['maintenance_duration'] == 'customize' ? 'checked' : '' }}
                                                    value="customize" id="customize">
                                                <label class="form-check-label"
                                                    for="customize">{{ translate('Customize') }}</label>
                                            </div>
                                        </div>
                                        <div class="row start-and-end-date">
                                            <div class="col-md-6">
                                                <label>{{ translate('Start Date') }}</label>
                                                <input type="datetime-local" class="form-control" name="start_date"
                                                    id="startDate"
                                                    value="{{ old('start_date', $selectedMaintenanceDuration['start_date'] ?? '') }}"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>{{ translate('End Date') }}</label>
                                                <input type="datetime-local" class="form-control" name="end_date"
                                                    id="endDate"
                                                    value="{{ old('end_date', $selectedMaintenanceDuration['end_date'] ?? '') }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <small id="dateError" class="form-text text-danger"
                                                    style="display: none;">{{ translate('Start date cannot be greater than end date.') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="advanceFeatureButtonDiv">
                            <div class="d-flex justify-content-center mt-3">
                                <a href="#" id="advanceFeatureToggle"
                                    class="d-block mb-3 maintenance-advance-feature-button">{{ translate('Advance Settings') }}</a>
                            </div>
                        </div>

                        <div class="row mt-4" id="advanceFeatureSection" style="display: none;">
                            <div class="col-xl-4">
                                <h3>{{ translate('Maintenance Massage') }}</h3>
                                <p>{{ translate('Select & type what massage you want to see your selected system when maintenance mode is active.') }}
                                </p>
                            </div>
                            <div class="col-xl-8">
                                <div class="border p-3">
                                    <div class="form-group">
                                        <label>{{ translate('Show Contact Info') }}</label>
                                        <div class="d-flex flex-wrap g-2 mb-3">
                                            <div class="form-check mr-2">
                                                <input class="form-check-input" type="checkbox" name="business_number"
                                                    {{ isset($selectedMaintenanceMessage) && $selectedMaintenanceMessage['business_number'] == 1 ? 'checked' : '' }}
                                                    id="businessNumber">
                                                <label class="form-check-label ml-1"
                                                    for="businessNumber">{{ translate('Business Number') }}</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="business_email"
                                                    {{ isset($selectedMaintenanceMessage) && $selectedMaintenanceMessage['business_email'] == 1 ? 'checked' : '' }}
                                                    id="businessEmail">
                                                <label class="form-check-label ml-1"
                                                    for="businessEmail">{{ translate('Business Email') }}</label>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Maintenance Message') }}
                                            <i class="tio-info-outined" data-toggle="tooltip" data-placement="top"
                                                title="{{ translate('The maximum character limit is 100') }}">
                                            </i>
                                        </label>
                                        <input type="text" class="form-control" name="maintenance_message"
                                            placeholder="We're Working On Something Special!" maxlength="100"
                                            value="{{ $selectedMaintenanceMessage['maintenance_message'] ?? '' }}">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Message Body') }}
                                            <i class="tio-info-outined" data-toggle="tooltip" data-placement="top"
                                                title="{{ translate('The maximum character limit is 255') }}">
                                            </i>
                                        </label>
                                        <textarea class="form-control" name="message_body" maxlength="255" rows="3"
                                            placeholder="{{ translate('Our system is currently undergoing maintenance to bring you an even tastier experience. Hang tight while we make the dishes.') }}">{{ isset($selectedMaintenanceMessage) && $selectedMaintenanceMessage['message_body'] ? $selectedMaintenanceMessage['message_body'] : '' }}</textarea>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" id="seeLessToggle"
                                        class="d-block mb-3 maintenance-advance-feature-button">{{ translate('See Less') }}</a>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="btn--container justify-content-end">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                id="cancelButton">{{ translate('Cancel') }}</button>
                            <button type="button" class="btn btn-primary call-demo"
                                onclick="validateMaintenanceMode()">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Checking -->
    <div class="modal fade" id="modalUncheckedDistanceExist" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="my-4">
                            <img src="{{ asset('public/assets/admin/svg/components/map-icon.svg') }}"
                                alt="Checked Icon">
                        </div>
                        <div class="my-4">
                            <h4>{{ translate('Turn off google Map') }}?</h4>
                            <p>{{ translate('One or more Branch delivery charge setup is based on distance, so you must need to update branch wise delivery charge setup to be Fixed or based on Area/Zip code.') }}
                            </p>
                        </div>
                        <div class="my-4">
                            <a class="btn btn-primary" target="_blank"
                                href="{{ route('admin.business-settings.store.delivery-fee-setup') }}">{{ translate('Go to Delivery Charge Setup') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for checking -->
    <div class="modal fade" id="modalUncheckedDistanceNotExist" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="">
                        <div class="text-center mb-5">
                            <img src="{{ asset('public/assets/admin/svg/components/map-icon.svg') }}"
                                alt="Unchecked Icon" class="mb-5">
                            <h4>{{ translate('Are You Sure') }}?</h4>
                            <p>{{ translate('By Turning On the Google Maps you need to setup following setting to get the map work properly.') }}
                            </p>
                        </div>

                        <div class="row g-2 ">
                            <div class="col-12 mb-2">
                                <a class="d-flex align-items-center border rounded px-3 py-2 g-1"
                                    href="{{ route('admin.customer.list') }}" target="_blank">
                                    <img src="{{ asset('public/assets/admin/svg/components/people.svg') }}"
                                        width="21" alt="">
                                    <span>{{ translate('Map Location in Customer Addresses') }}</span>
                                </a>
                            </div>
                            <div class="col-12 mb-2">
                                <a class="d-flex align-items-center border rounded px-3 py-2 g-1"
                                    href="{{ route('admin.branch.list') }}" target="_blank">
                                    <img src="{{ asset('public/assets/admin/svg/components/branch.svg') }}"
                                        width="21" alt="">
                                    <span>{{ translate('Map in Branch Coverage Area') }}</span>
                                </a>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="d-flex align-items-center border rounded px-3 py-2 g-1">
                                    <img src="{{ asset('public/assets/admin/svg/components/delivery-car.svg') }}"
                                        width="21" alt="">
                                    <span
                                        class="text-primary">{{ translate('Deliveryman Live Location on Customer & Deliveryman App & web') }}</span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <a class="d-flex align-items-center border rounded px-3 py-2 g-1"
                                    href="{{ route('admin.business-settings.store.delivery-fee-setup') }}"
                                    target="_blank">
                                    <img src="{{ asset('public/assets/admin/svg/components/delivery-charge.svg') }}"
                                        width="21" alt="">
                                    <span>{{ translate('Delivery Charge Setup') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center my-4 gap-3">
                            <button class="btn btn-secondary ml-2"
                                id="cancelButtonNotExist">{{ translate('Cancel') }}</button>
                            <button class="btn btn-danger" id="turnOffButton">{{ translate('Ok, Turn Off') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Checking -->
    <div class="modal fade" id="modalCheckedModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="my-4">
                            <img src="{{ asset('public/assets/admin/svg/components/map-icon.svg') }}"
                                alt="Checked Icon">
                        </div>
                        <div class="my-4">
                            <h4>{{ translate('Turn on google Map') }}?</h4>
                            <p>{{ translate('By turning on this option, you can be able to see the map on customer app & website, admin panel, branch panel and deliveryman app. You can now also setup your delivery charges based on distance(km) from ') }}
                                <a class="" target="_blank"
                                    href="{{ route('admin.business-settings.store.delivery-fee-setup') }}">{{ translate('This Page') }}</a>
                            </p>
                            <p>{{ translate('note') }}:
                                {{ translate('Currently Delivery Charge is set based on Zip Code/Area wise or Based on Fixed Delivery Charge') }}
                            </p>
                        </div>
                        <div class="my-4">
                            <button class="btn btn-primary" id="turnOnButton">{{ translate('Yes, Turn On') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        @php($time_zone = \App\Model\BusinessSetting::where('key', 'time_zone')->first())
        @php($time_zone = $time_zone->value ?? null)
        $('[name=time_zone]').val("{{ $time_zone }}");

        @php($language = \App\Model\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        let language = <?php echo $language; ?>;
        $('[id=language]').val(language);

        function readURL(input, viewer) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + viewer).attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this, 'viewer');
        });
        $("#customFileEg2").change(function() {
            readURL(this, 'viewer_2');
        });

        $("#language").on("change", function() {
            $("#alert_box").css("display", "block");
        });
    </script>

    <script>
        @if (env('APP_MODE') == 'demo')
            function maintenance_mode() {
                toastr.info('{{ translate('Disabled for demo version!') }}')
            }
        @else
            function maintenance_mode() {
                Swal.fire({
                    title: '{{ translate('Are you sure?') }}',
                    text: '{{ translate('Be careful before you turn on/off maintenance mode') }}',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#377dff',
                    cancelButtonText: '{{ translate('No') }}',
                    confirmButtonText: '{{ translate('Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $.get({
                            url: '{{ route('admin.business-settings.store.maintenance-mode') }}',
                            contentType: false,
                            processData: false,
                            beforeSend: function() {
                                $('#loading').show();
                            },
                            success: function(data) {
                                toastr.success(data.message);
                                location.reload();
                            },
                            complete: function() {
                                $('#loading').hide();
                            },
                        });
                    } else {
                        location.reload();
                    }
                })
            };
        @endif

        function changeBusinessSettings(route) {
            $.get({
                url: route,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    toastr.success(data.message);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }

        $('.change-currency-position').on('click', function() {
            let route = $(this).data('route');
            changeBusinessSettings(route);
        })

        $('.change-self-pickup-status').on('click', function() {
            let route = $(this).data('route');
            changeBusinessSettings(route);
        })

        $('.deliveryman-self-registration').on('click', function() {
            let route = $(this).data('route');
            changeBusinessSettings(route);
        })

        $('.guest-checkout-status').on('click', function() {
            let route = $(this).data('route');
            changeBusinessSettings(route);
        })

        function max_amount_status(route) {

            $.get({
                url: route,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    setTimeout(function() {
                        location.reload(true);
                    }, 1000);
                    if (data.status == 1) {
                        toastr.success(data.message);
                    } else {
                        toastr.warning(data.message);
                    }
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }

        function partial_payment_status(route) {

            $.get({
                url: route,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    toastr.success(data.message);
                    setTimeout(function() {
                        location.reload(true);
                    }, 1000);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }
    </script>

    <script>
        $('.maintenance-mode-show').click(function() {
            $('#maintenance-mode-modal').modal('show');
        });

        $(document).ready(function() {
            var initialMaintenanceMode = $('#maintenance-mode-input').is(':checked');

            $('#maintenance-mode-modal').on('show.bs.modal', function() {
                var initialMaintenanceModeModel = $('#maintenance-mode-input').is(':checked');
                $('#maintenance-mode-checkbox').prop('checked', initialMaintenanceModeModel);
            });

            $('#maintenance-mode-modal').on('hidden.bs.modal', function() {
                $('#maintenance-mode-input').prop('checked', initialMaintenanceMode);
            });

            $('#cancelButton').click(function() {
                $('#maintenance-mode-input').prop('checked', initialMaintenanceMode);
                $('#maintenance-mode-modal').modal('hide');
            });

            $('#maintenance-mode-checkbox').change(function() {
                $('#maintenance-mode-input').prop('checked', $(this).is(':checked'));
            });
        });

        $(document).ready(function() {
            $('#advanceFeatureToggle').click(function(event) {
                event.preventDefault();
                $('#advanceFeatureSection').show();
                $('#advanceFeatureButtonDiv').hide();
            });

            $('#seeLessToggle').click(function(event) {
                event.preventDefault();
                $('#advanceFeatureSection').hide();
                $('#advanceFeatureButtonDiv').show();
            });

            $('#allSystem').change(function() {
                var isChecked = $(this).is(':checked');
                $('.system-checkbox').prop('checked', isChecked);
            });

            // If any other checkbox is unchecked, also uncheck "All System"
            $('.system-checkbox').not('#allSystem').change(function() {
                if (!$(this).is(':checked')) {
                    $('#allSystem').prop('checked', false);
                } else {
                    // Check if all system-related checkboxes are checked
                    if ($('.system-checkbox').not('#allSystem').length === $('.system-checkbox:checked')
                        .not('#allSystem').length) {
                        $('#allSystem').prop('checked', true);
                    }
                }
            });

            $(document).ready(function() {
                var startDate = $('#startDate');
                var endDate = $('#endDate');
                var dateError = $('#dateError');

                function updateDatesBasedOnDuration(selectedOption) {
                    if (selectedOption === 'one_day' || selectedOption === 'one_week') {
                        var now = new Date();
                        var timezoneOffset = now.getTimezoneOffset() * 60000;
                        var formattedNow = new Date(now.getTime() - timezoneOffset).toISOString().slice(0,
                            16);

                        if (selectedOption === 'one_day') {
                            var end = new Date(now);
                            end.setDate(end.getDate() + 1);
                        } else if (selectedOption === 'one_week') {
                            var end = new Date(now);
                            end.setDate(end.getDate() + 7);
                        }

                        var formattedEnd = new Date(end.getTime() - timezoneOffset).toISOString().slice(0,
                            16);

                        startDate.val(formattedNow).prop('readonly', false).prop('required', true);
                        endDate.val(formattedEnd).prop('readonly', false).prop('required', true);
                        $('.start-and-end-date').removeClass('opacity');
                        dateError.hide();
                    } else if (selectedOption === 'until_change') {
                        startDate.val('').prop('readonly', true).prop('required', false);
                        endDate.val('').prop('readonly', true).prop('required', false);
                        $('.start-and-end-date').addClass('opacity');
                        dateError.hide();
                    } else if (selectedOption === 'customize') {
                        startDate.prop('readonly', false).prop('required', true);
                        endDate.prop('readonly', false).prop('required', true);
                        $('.start-and-end-date').removeClass('opacity');
                        dateError.hide();
                    }
                }

                function validateDates() {
                    var start = new Date(startDate.val());
                    var end = new Date(endDate.val());
                    if (start > end) {
                        dateError.show();
                        startDate.val('');
                        endDate.val('');
                    } else {
                        dateError.hide();
                    }
                }

                // Initial load
                var selectedOption = $('input[name="maintenance_duration"]:checked').val();
                updateDatesBasedOnDuration(selectedOption);

                // When maintenance duration changes
                $('input[name="maintenance_duration"]').change(function() {
                    var selectedOption = $(this).val();
                    updateDatesBasedOnDuration(selectedOption);
                });

                // When start date or end date changes
                $('#startDate, #endDate').change(function() {
                    $('input[name="maintenance_duration"][value="customize"]').prop('checked',
                    true);
                    startDate.prop('readonly', false).prop('required', true);
                    endDate.prop('readonly', false).prop('required', true);
                    validateDates();
                });
            });
        });

        $('#google_map_status').change(function() {
            if ($(this).is(':checked')) {
                $('#modalCheckedModal').modal('show');
            } else {
                $.ajax({
                    url: '{{ route('admin.business-settings.store.check-distance-based-delivery') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.hasDistanceBasedDelivery) {
                            $('#modalUncheckedDistanceExist').modal('show');
                            $('#google_map_status').prop('checked', true);
                        } else {
                            $('#modalUncheckedDistanceNotExist').modal('show');
                        }
                    }
                });
            }
        });

        let turnOnConfirmed = false; // Flag to track if "Yes, Turn On" was clicked

        // Handle the "Yes, Turn On" button click inside the modalCheckedModal
        $('#turnOnButton').click(function() {
            turnOnConfirmed = true; // Set flag when "Yes, Turn On" is clicked
            $('#modalCheckedModal').modal('hide'); // Hide the modal
        });

        // Revert checkbox state when modalCheckedModal is closed without confirmation
        $('#modalCheckedModal').on('hidden.bs.modal', function() {
            if (!turnOnConfirmed) {
                $('#google_map_status').prop('checked', false); // Revert to unchecked if not confirmed
            }
            turnOnConfirmed = false; // Reset the flag after modal closes
        });

        let turnOffConfirmed = false;

        $('#cancelButtonNotExist').click(function() {
            $('#google_map_status').prop('checked', true);
            $('#modalUncheckedDistanceNotExist').modal('hide');
            turnOffConfirmed = false;
        });

        $('#turnOffButton').click(function() {
            turnOffConfirmed = true;
            $('#modalUncheckedDistanceNotExist').modal('hide');
        });

        $('#modalUncheckedDistanceNotExist').on('hidden.bs.modal', function() {
            if (!turnOffConfirmed) {
                $('#google_map_status').prop('checked', true);
            }
            turnOffConfirmed = false;
        });
    </script>

    <script>
        function validateMaintenanceMode() {
            const maintenanceModeChecked = $('#maintenance-mode-checkbox').is(':checked');

            if (maintenanceModeChecked) {
                const isAnySystemSelected = $('.system-checkbox').is(':checked');

                if (!isAnySystemSelected) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ translate('Please select a system') }}!',
                        text: '{{ translate('You must select at least one system when activating Maintenance Mode.') }}',
                        confirmButtonText: '{{ translate('OK') }}',
                        confirmButtonColor: '#107980',
                    });
                    return false;
                }
            }

            $('#maintenanceModeForm').submit();
        }

        $(document).ready(function() {
            const $orderNotificationCheckbox = $('#admin_order_notification');
            const $notificationTypeRadios = $('input[name="admin_order_notification_type"]');

            // Function to toggle the disabled state of notification type radios
            function toggleNotificationType() {
                const isChecked = $orderNotificationCheckbox.is(':checked');
                $notificationTypeRadios.prop('disabled', !isChecked);
            }

            // Initial call to set the correct state on page load
            toggleNotificationType();

            // Add event listener to handle changes
            $orderNotificationCheckbox.on('change', toggleNotificationType);
        });
    </script>
@endpush
