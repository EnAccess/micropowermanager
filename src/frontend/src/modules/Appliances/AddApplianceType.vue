<template>
  <div>
    <widget
      :hidden="!addNewApplianceType"
      :title="$tc('phrases.applianceType', 3)"
      color="secondary"
    >
      <md-card>
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-card-content>
              <form class="md-layout md-gutter" ref="applianceForm">
                <div class="md-layout-item md-size-100 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label>{{ $tc("words.name") }}</label>
                    <md-input
                      v-model="applianceTypeService.applianceType.name"
                      :placeholder="$tc('words.name')"
                      type="text"
                      :name="$tc('words.name')"
                      id="appliance"
                      v-validate="'required|min:4'"
                    ></md-input>
                    <span class="md-error">
                      {{ errors.first($tc("words.name")) }}
                    </span>
                  </md-field>
                </div>
              </form>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </md-card-content>
          </div>
        </div>
        <md-card-actions>
          <md-button
            class="md-raised md-primary"
            @click="saveApplianceType()"
            :disabled="loading"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button class="md-raised" @click="closeAddComponent()">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { ApplianceTypeService } from "@/services/ApplianceTypeService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "AddApplianceType",
  components: { Widget },
  props: {
    addNewApplianceType: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      applianceTypeService: new ApplianceTypeService(),
      loading: false,
      isMounted: false,
    }
  },
  mounted() {
    this.isMounted = true
  },
  methods: {
    async saveApplianceType() {
      let validation = await this.$validator.validateAll()
      if (!validation) {
        return
      }
      try {
        this.loading = true
        await this.applianceTypeService.createApplianceType()

        this.loading = false
        this.alertNotify("success", this.$tc("phrases.newApplianceSku", 1))
        EventBus.$emit("ApplianceTypeAdded")
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },

    closeAddComponent() {
      EventBus.$emit("addApplianceTypeClosed", false)
    },
  },
  watch: {
    addNewApplianceType(value) {
      if (value) {
        this.errors.clear()
      }
    },
  },
}
</script>
