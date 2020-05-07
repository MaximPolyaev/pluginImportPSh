<div class="panel">
  <div class="panel-heading"><i class="material-icons">import_export</i>
    {l s='Import products from CSV and XML files' d='Modules.Importpalmira.Step'}</div>
  <div class="mp-stepper-horizontal">
    <div class="mp-step active">
      <div class="mp-step-circle"><span>1</span></div>
      <div class="mp-step-title">{l s='Upload your file' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-optional mp-error-optional">{l s='Error' d='Modules.Importpalmira.Step'}<i class="material-icons">error_outline</i>
      </div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step">
      <div class="mp-step-circle"><span>2</span></div>
      <div class="mp-step-title">{l s='Match your data' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-optional mp-done-optional">{l s='Success' d='Modules.Importpalmira.Step'}<i
                class="material-icons">done</i></div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
    <div class="mp-step">
      <div class="mp-step-circle"><span>3</span></div>
      <div class="mp-step-title">{l s='Products loaded' d='Modules.Importpalmira.Step'}</div>
      <div class="mp-step-bar-left"></div>
      <div class="mp-step-bar-right"></div>
    </div>
  </div>
  <hr>
  <div>
    {$form_view|escape:'UTF-8'}
  </div>
</div>
