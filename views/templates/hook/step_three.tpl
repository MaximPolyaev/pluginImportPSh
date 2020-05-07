<div class="panel">
  <div class="panel-heading"><i class="material-icons">import_export</i>
    {l s='Import products from CSV and XML files' d='Modules.Importpalmira.Stepone'}</div>
  <div class="mp-stepper-horizontal">
    <div class="mp-step active">
      <div class="mp-step-circle"><span>1</span></div>
      <div class="mp-step-title">{l s='Upload your file' d='Modules.Importpalmira.Stepone'}</div>
      <div class="mp-step-optional mp-error-optional">{l s='Error' d='Modules.Importpalmira.Stepone'}<i
                class="material-icons">error_outline</i>
      </div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step active">
      <div class="mp-step-circle"><span>2</span></div>
      <div class="mp-step-title">{l s='Match your data' d='Modules.Importpalmira.Stepone'}</div>
      <div class="mp-step-optional mp-done-optional">{l s='Success' d='Modules.Importpalmira.Stepone'}<i
                class="material-icons">done</i></div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step active">
      <div class="mp-step-circle"><span>3</span></div>
      <div class="mp-step-title">{l s='Products loaded' d='Modules.Importpalmira.Stepone'}</div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
  </div>
  <hr>
  <div>

    {*    {$form_step_two|escape:'UTF-8'}*}
    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
      <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
      <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>
  </div>
</div>
