<template>
  <div
    class="buildamic-set"
    :class="[
      `buildamic-set-${field.config.statamic_settings.handle}-fieldtype`,
    ]"
  >
    <admin-label :field="field" />
    <vue-tabs :id="field.uuid">
      <vue-tab name="Content" selected="selected">
        <div class="publish-fields @container w-full">
            <set-field
              v-for="(val, key) in field.value"
              :key="key + field.uuid"
              :value="val"
              :handle="key"
              :field="field"
              :type="field.config.statamic_settings.handle"
              :setDefaults="setDefaults"
              :fieldDefaults="fieldDefaults"
            />
        </div>
        <error-display />
      </vue-tab>
      <vue-tab name="Design">
        <div class="publish-fields @container w-full">
          <design-tab :field="field" :fieldDefaults="setDefaults" />
        </div>
      </vue-tab>
      <vue-tab name="Options">
        <div class="publish-fields @container w-full">
          <options-tab :field="field" />
        </div>
      </vue-tab>
    </vue-tabs>
  </div>
</template>

<script>
import SetField from "./SetField.vue";
import OptionsTab from "../shared/OptionsTab.vue";
import DesignTab from "../shared/DesignTab.vue";
import AdminLabel from "../shared/AdminLabel.vue";
import ErrorDisplay from "../shared/ErrorDisplay.vue";

export default {
  props: {
    field: {
      type: Object,
      required: true,
    },
    fieldDefaults: Object,
  },
  computed: {
    setDefaults() {
      return this.fieldDefaults[
        this.field.config.statamic_settings.handle
      ].fields.reduce((acc, cur) => {
        acc[cur.handle] = cur;
        return acc;
      }, {});
    },
  },
  components: {
    SetField,
    OptionsTab,
    DesignTab,
    AdminLabel,
    ErrorDisplay,
  },
};
</script>
