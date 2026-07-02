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
</style>
