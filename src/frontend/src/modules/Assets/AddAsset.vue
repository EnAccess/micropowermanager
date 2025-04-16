<template>
  <div>
    <widget :hidden="!addNewAsset" title="Add Appliance" color="red">
      <md-card>
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-card-content>
              <form class="md-layout md-gutter" ref="assetForm">
                <div class="md-layout-item md-size-33 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.appliance')),
                    }"
                  >
                    <label for="appliance">
                      {{ $tc("words.appliance") }}
                    </label>
                    <md-select
                      :name="$tc('words.appliance')"
                      id="appliance"
                      v-model="applianceService.appliance.assetTypeId"
                    >
                      <md-option disabled value>
                        --{{ $tc("words.select") }}--
                      </md-option>
                      <md-option
                        :value="applianceType.id"
                        v-for="applianceType in assetTypeService.list"
                        :key="applianceType.id"
                      >
                        {{ applianceType.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first($tc("words.appliance")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-33 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label>{{ $tc("words.name") }}</label>
                    <md-input
                      v-model="applianceService.appliance.name"
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
                <div class="md-layout-item md-size-33 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.price')),
                    }"
                  >
                    <label>{{ $tc("words.price") }}</label>
                    <md-input
                      v-model="applianceService.appliance.price"
                      :placeholder="$tc('words.price')"
                      type="text"
                      :name="$tc('words.price')"
                      id="asset_price"
                      v-validate="'required|numeric'"
                    ></md-input>
                    <span class="md-error">
                      {{ errors.first($tc("words.price")) }}
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
            @click="saveAppliance()"
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
import { ApplianceService } from "@/services/ApplianceService"
import { AssetTypeService } from "@/services/AssetTypeService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "AddAsset",
  mixins: [notify],
  components: { Widget },
  props: {
    addNewAsset: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      assetTypeService: new AssetTypeService(),
      applianceService: new ApplianceService(),
      loading: false,
    }
  },
  mounted() {
    this.getAssetTypes()
  },
  methods: {
    async saveAppliance() {
      let validation = await this.$validator.validateAll()
      if (!validation) {
        return
      }
      try {
        this.loading = true
        const appliances = await this.applianceService.createAppliance()

        this.loading = false
        this.alertNotify("success", this.$tc("phrases.newAppliance", 1))
        EventBus.$emit("applianceAdded", appliances)
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    closeAddComponent() {
      EventBus.$emit("addApplianceClosed", false)
    },
    async getAssetTypes() {
      await this.assetTypeService.getAssetsTypes()
    },
  },
}
</script>

<style scoped></style>
