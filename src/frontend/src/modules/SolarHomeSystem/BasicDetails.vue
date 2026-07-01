<template>
  <widget
    :title="$tc('SolarHomeSystem')"
    color="primary"
    :subscriber="'shs-details'"
    :button="!editing"
    :button-text="$tc('words.edit')"
    button-icon="edit"
    @widgetAction="startEdit"
  >
    <md-list class="md-double-line">
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.serialNumber") }}</span>
          <span>{{ shs.serialNumber }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <md-field v-if="editing" class="inline-edit-field">
          <label>{{ $tc("words.manufacturer") }}</label>
          <md-select v-model="form.manufacturerId" name="manufacturer">
            <md-option
              v-for="manufacturer in manufacturers"
              :value="manufacturer.id"
              :key="manufacturer.id"
            >
              {{ manufacturer.name }}
            </md-option>
          </md-select>
        </md-field>
        <div v-else class="md-list-item-text">
          <span>{{ $tc("words.manufacturer") }}</span>
          <span>{{ shs.manufacturer?.name || "-" }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <md-field v-if="editing" class="inline-edit-field">
          <label>{{ $tc("words.appliance") }}</label>
          <md-select v-model="form.applianceId" name="appliance">
            <md-option
              v-for="appliance in appliances"
              :value="appliance.id"
              :key="appliance.id"
            >
              {{ appliance.name }}
            </md-option>
          </md-select>
        </md-field>
        <div v-else class="md-list-item-text">
          <span>{{ $tc("words.appliance") }}</span>
          <span>{{ shs.appliance?.name || "-" }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.lastUpdate") }}</span>
          <span>{{ timeForTimeZone(shs.updatedAt) }}</span>
        </div>
      </md-list-item>
    </md-list>

    <div v-if="!editing" class="verify-action">
      <md-button
        class="md-raised md-primary"
        :disabled="verifying"
        @click="verifyMapping"
      >
        <md-icon>fact_check</md-icon>
        {{ $tc("phrases.verifyDeviceMapping") }}
      </md-button>
    </div>

    <md-dialog :md-active.sync="showVerifyDialog">
      <md-dialog-title>{{ $tc("phrases.deviceMapping") }}</md-dialog-title>
      <md-dialog-content>
        <md-progress-bar md-mode="indeterminate" v-if="verifying" />
        <div v-else-if="verifyResult">
          <md-empty-state
            v-if="!verifyResult.supported"
            md-icon="info"
            :md-label="$tc('phrases.verificationUnavailable')"
            :md-description="
              $tc('phrases.verificationUnavailableDescription', 1, {
                manufacturer:
                  shs.manufacturer?.name || $tc('words.manufacturer'),
              })
            "
          />
          <md-list v-else-if="verifyResult.mapped" class="md-double-line">
            <md-list-item>
              <md-icon class="status-ok">check_circle</md-icon>
              <div class="md-list-item-text">
                <span>{{ $tc("words.status") }}</span>
                <span>
                  {{
                    $tc("phrases.deviceMapped", 1, {
                      manufacturer: shs.manufacturer?.name,
                    })
                  }}
                </span>
              </div>
            </md-list-item>
            <template v-for="(value, key) in verifyResult.device">
              <md-divider :key="`divider-${key}`"></md-divider>
              <md-list-item
                v-if="isExpandable(value)"
                :key="`expand-${key}`"
                md-expand
              >
                <span class="md-list-item-text">{{ key }}</span>
                <md-list slot="md-expand">
                  <md-list-item
                    v-for="(nestedValue, nestedKey) in value"
                    :key="`${key}-${nestedKey}`"
                  >
                    <div class="md-list-item-text">
                      <span>{{ nestedKey }}</span>
                      <span>{{ nestedValue }}</span>
                    </div>
                  </md-list-item>
                </md-list>
              </md-list-item>
              <md-list-item v-else :key="key">
                <div class="md-list-item-text">
                  <span>{{ key }}</span>
                  <span>{{ value }}</span>
                </div>
              </md-list-item>
            </template>
          </md-list>
          <md-empty-state
            v-else
            md-icon="error_outline"
            :md-label="$tc('phrases.deviceNotMapped')"
            :md-description="
              $tc('phrases.deviceNotMappedDescription', 1, {
                serial: shs.serialNumber,
                manufacturer:
                  shs.manufacturer?.name || $tc('words.manufacturer'),
              })
            "
          />
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-primary" @click="showVerifyDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <div v-if="editing" class="edit-actions">
      <md-button class="md-raised md-primary" :disabled="loading" @click="save">
        {{ $tc("words.save") }}
      </md-button>
      <md-button class="md-raised" :disabled="loading" @click="cancelEdit">
        {{ $tc("words.cancel") }}
      </md-button>
    </div>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
  </widget>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { ApplianceService } from "@/services/ApplianceService.js"
import { ManufacturerService } from "@/services/ManufacturerService.js"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService.js"
import Widget from "@/shared/Widget.vue"

const MANUFACTURER_TYPE = "shs"
const APPLIANCE_TYPE_ID = 1

export default {
  name: "BasicDetails",
  mixins: [notify, timing],
  components: {
    Widget,
  },
  props: {
    shs: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      manufacturerService: new ManufacturerService(),
      applianceService: new ApplianceService(),
      editing: false,
      loading: false,
      verifying: false,
      showVerifyDialog: false,
      verifyResult: null,
      form: {
        manufacturerId: null,
        applianceId: null,
      },
    }
  },
  computed: {
    manufacturers() {
      return this.manufacturerService.list.filter(
        (manufacturer) => manufacturer.type === MANUFACTURER_TYPE,
      )
    },
    appliances() {
      return this.applianceService.list.filter(
        (appliance) => appliance.applianceTypeId === APPLIANCE_TYPE_ID,
      )
    },
  },
  mounted() {
    this.$emit("widget-loaded", "details")
  },
  methods: {
    startEdit() {
      this.form.manufacturerId = this.shs.manufacturer?.id ?? null
      this.form.applianceId = this.shs.appliance?.id ?? null
      if (!this.manufacturerService.list.length) {
        this.manufacturerService.getManufacturers()
      }
      if (!this.applianceService.list.length) {
        this.applianceService.getAppliances()
      }
      this.editing = true
    },
    cancelEdit() {
      this.editing = false
    },
    isExpandable(value) {
      return value !== null && typeof value === "object"
    },
    async verifyMapping() {
      this.verifying = true
      this.verifyResult = null
      this.showVerifyDialog = true
      try {
        this.verifyResult = await this.solarHomeSystemService.getDeviceInfo(
          this.shs.id,
        )
      } catch (e) {
        this.showVerifyDialog = false
        this.alertNotify("error", e.message)
      } finally {
        this.verifying = false
      }
    },
    async save() {
      if (!this.form.manufacturerId || !this.form.applianceId) {
        this.alertNotify("error", "Please fill all required fields.")
        return
      }

      this.loading = true
      try {
        const updated = await this.solarHomeSystemService.updateSolarHomeSystem(
          this.shs.id,
          {
            manufacturerId: this.form.manufacturerId,
            applianceId: this.form.applianceId,
          },
        )
        this.alertNotify(
          "success",
          `${this.$tc("words.shs", 1)} ${this.$tc("words.update", 2)}`,
        )
        this.editing = false
        this.$emit("updated", updated)
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.inline-edit-field {
  margin: 4px 0;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 8px 16px 16px;
}

.verify-action {
  display: flex;
  justify-content: flex-end;
  padding: 8px 16px 16px;
}

.status-ok {
  color: #43a047 !important;
}
</style>
