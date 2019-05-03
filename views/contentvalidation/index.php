<style>
    .uk-modal-details .uk-modal-dialog {
        height: 85%;
    }
</style>

<div>
    <ul class="uk-breadcrumb">
        <li class="uk-active"><span>@lang('Content Validation')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>

    <div class="uk-text-xlarge uk-text-center uk-text-primary uk-margin-large-top" show="{ loading }">
        <i class="uk-icon-spinner uk-icon-spin"></i>
    </div>

    <div class="uk-text-large uk-text-center uk-margin-large-top uk-text-muted" show="{ !loading && !hasViolations}">
        <img class="uk-svg-adjust" src="@url('contentvalidation:icon.svg')" width="100" height="100" alt="@lang('Content Validation')" data-uk-svg />
        <p>@lang('No violations found')</p>
    </div>

    <div class="uk-form uk-clearfix" show="{!loading && hasViolations}">

        <div each="{errors, $index in violations}">
            <h2 class="uk-text-bold uk-flex uk-flex-middle">
                <img class="uk-margin-small-right"
                     src="/modules/Collections/icon.svg"
                     width="25" height="25"
                     alt="Collection"
                     if="{errors[0].module === 'Collection'}"
                />
                <span>{errors[0].entity}</span>
                <span class="uk-badge uk-text-small uk-flex uk-flex-bottom uk-badge-danger"
                      style="margin-left: 10px; margin-top: 5px"
                      if="{numberOfErrors[$index] > 0 }">
                     { numberOfErrors[$index] }
                </span>
                <span class="uk-badge uk-text-small uk-flex uk-flex-bottom uk-badge-warning"
                      style="margin-left: 10px; margin-top: 5px"
                      if="{numberOfWarnings[$index] > 0 }">
                     { numberOfWarnings[$index] }
                </span>
            </h2>

            <table class="uk-table uk-table-tabbed uk-table-striped uk-margin-top" if="{ !loading && errors.length }">
                <thead>
                <tr>
                    <th class="uk-text-small uk-link-muted uk-noselect" width="70">
                        @lang('Type')
                    </th>
                    <th class="uk-text-small uk-link-muted uk-noselect" width="70">
                        @lang('Item Name')
                    </th>
                    <th class="uk-text-small uk-link-muted uk-noselect" width="70">
                        @lang('Error')
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr each="{error, $index in errors}" class="">
                    <td>
                    <span class="uk-badge uk-text-small uk-badge-danger" if="{error.violationtype == 'Error'}">
                        &nbsp;<i class="uk-icon-exclamation-circle uk-icon-justify"></i>{ error.violationtype }
                    </span>
                        <span class="uk-badge uk-text-small uk-badge-warning"  if="{error.violationtype == 'Warning'}">
                        <i class="uk-icon-warning uk-icon-justify"></i>{ error.violationtype }
                    </span>
                    </td>
                    <td>
                        <a class="uk-link-muted"
                           href="@route('/collections/entry/{error.entity}/{error._id}')"
                           if="{error.module == 'Collection'}">
                            { error.name }
                        </a>
                    </td>
                    <td>
                        { error.violation }
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>

    <script type="view/script">

    var $this = this;
    $this.deploy = {};
    $this.loading = false;
    $this.violations = {{ json_encode($violations) }};
    $this.hasViolations = {{ count($violations) ? 'true' : 'false' }};
    $this.numberOfErrors = {};
    $this.numberOfWarnings = {};
    Object.keys($this.violations).forEach(function(key) {
        $this.numberOfErrors[key] = $this.violations[key].filter(function(violation) { return violation.violationtype === 'Error'; }).length;
        $this.numberOfWarnings[key] = $this.violations[key].filter(function(violation) { return violation.violationtype === 'Warning'; }).length;
      });

    this.on('mount', function() {

      $this.numberOfErrors = {};
      $this.numberOfWarnings = {};
      $this.loading = false;
      $this.modal = UIkit.modal(App.$('.uk-modal-details', this.root), {modal:true});
    });
  </script>

</div>
