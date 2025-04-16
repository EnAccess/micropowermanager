<template>
  <div>
    <widget
      :hidden="!addNewAssetType"
      :title="$tc('phrases.applianceType', 3)"
      color="red"
    >
      <md-card>
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-card-content>
              <form class="md-layout md-gutter" ref="assetForm">
                <div class="md-layout-item md-size-100 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label>{{ $tc("words.name") }}</label>
                    <md-input
                      v-model="assetTypeService.assetType.name"
                      :placeholder="$tc('words.name')"
                      type="text"
                      :name="$tc('words.name')"
                      id="asset"
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
            @click="saveAssetType()"
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
import { AssetTypeService } from "@/services/AssetTypeService"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "AddAssetType",
  components: { Widget },
  props: {
    addNewAssetType: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      assetTypeService: new AssetTypeService(),
      loading: false,
      isMounted: false,
    }
  },
  mounted() {
    this.isMounted = true
  },
  methods: {
    async saveAssetType() {
      let validation = await this.$validator.validateAll()
      if (!validation) {
        return
      }
      try {
        this.loading = true
        await this.assetTypeService.createAssetType()

        this.loading = false
        this.alertNotify("success", this.$tc("phrases.newAppliance", 1))
        EventBus.$emit("AssetTypeAdded")
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },

    closeAddComponent() {
      EventBus.$emit("addAssetTypeClosed", false)
    },
  },
  watch: {
    addNewAssetType(value) {
      if (value) {
        this.errors.clear()
      }
    },
  },
}
</script>
