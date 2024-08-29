<template>
  <div>
    <md-dialog
      :md-active.sync="showAddEBike"
      style="max-width: 60rem; margin: auto; overflow: auto"
    >
      <md-dialog-title>
        <h4
          style="font-size: 1.2rem; margin: 0; border-bottom: solid 1px #dedede"
        >
          {{ $tc("words.e_bike", 1) }}
        </h4>
      </md-dialog-title>

      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <form class="md-layout md-gutter" data-vv-scope="eBike-add-form">
              <!-- serial number -->
              <div class="md-layout-item md-size-100 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('eBike-add-form.serial_number'),
                  }"
                >
                  <label for="serial_number">
                    {{ $tc("phrases.serialNumber") }}
                  </label>
                  <md-input
                    id="serial_number"
                    name="serial_number"
                    v-model="eBikeService.eBike.serialNumber"
                    v-validate="'required|min:8|max:15'"
                  />
                  <span class="md-error">
                    {{ errors.first("eBike-add-form.serial_number") }}
                  </span>
                </md-field>
              </div>

              <!--manufacturer list-->
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('eBike-add-form.manufacturer'),
                  }"
                >
                  <label for="manufacturers">
                    {{ $tc("words.manufacturer") }}
                  </label>
                  <md-select
                    v-model="eBikeService.eBike.manufacturerId"
                    name="manufacturer"
                    id="manufacturer"
                    v-validate="'required'"
                  >
                    <md-option
                      v-for="manufacturer in manufacturers"
                      :value="manufacturer.id"
                      :key="manufacturer.id"
                    >
                      {{ manufacturer.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("eBike-add-form.manufacturer") }}
                  </span>
                </md-field>
              </div>

              <!--asset list-->
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('eBike-add-form.appliance'),
                  }"
                >
                  <label for="appliances">
                    {{ $tc("words.appliance") }}
                  </label>
                  <md-select
                    v-model="eBikeService.eBike.assetId"
                    name="appliance"
                    id="appliance"
                    v-validate="'required'"
                  >
                    <md-option
                      v-for="appliance in appliances"
                      :value="appliance.id"
                      :key="appliance.id"
                    >
                      {{ appliance.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("eBike-add-form.appliance") }}
                  </span>
                </md-field>
              </div>
            </form>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          role="button"
          class="md-raised md-primary"
          :disabled="loading"
          @click="save"
        >
          {{ $tc("words.save") }}
        </md-button>
        <md-button role="button" class="md-raised" @click="cancel">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </md-dialog>
  </div>
</template>

<script>
import { ManufacturerService } from "@/services/ManufacturerService"
import { ApplianceService } from "@/services/ApplianceService"
import { timing, notify } from "@/mixins"
import { EBikeService } from "@/services/EBikeService"

//these are fixed values in the database
const MANUFACTURER_TYPE = "e-bike"
const APPLIANCE_TYPE_ID = 2

export default {
  name: "AddEBikeModal",
  mixins: [notify, timing],
  props: {
    showAddEBike: {
      default: false,
      type: Boolean,
    },
  },
  data() {
    return {
      eBikeService: new EBikeService(),
      manufacturerService: new ManufacturerService(),
      applianceService: new ApplianceService(),
      loading: false,
    }
  },
  beforeMount() {
    this.manufacturerService.getManufacturers()
    this.applianceService.getAppliances()
  },
  methods: {
    async save() {
      const validator = await this.$validator.validateAll("eBike-add-form")
      if (validator) {
        this.loading = true
        try {
          const createdEBike = await this.eBikeService.createEBike()
          this.alertNotify("success", this.$tc("phrases.newEBike"))
          this.$emit("created", createdEBike)
        } catch (e) {
          this.alertNotify("error", e.message)
        }
        this.loading = false
      }
    },
    cancel() {
      this.$emit("hideAddEBike")
    },
  },
  computed: {
    manufacturers() {
      return this.manufacturerService.list.filter(
        (manufacturer) => manufacturer.type === MANUFACTURER_TYPE,
      )
    },
    appliances() {
      return this.applianceService.list.filter(
        (appliance) => appliance.assetTypeId === APPLIANCE_TYPE_ID,
      )
    },
  },
}
</script>
