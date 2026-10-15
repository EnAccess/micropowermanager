<template>
  <md-dialog :md-active.sync="dialogVisible" class="openpaygo-registration">
    <md-dialog-title>
      {{ $tc("phrases.openPaygoRegistration") }}
    </md-dialog-title>
    <md-dialog-content>
      <p>{{ $tc("phrases.openPaygoRegistrationHelp") }}</p>
      <p v-if="manufacturerOptionsLoaded && !manufacturers.length">
        {{ $tc("phrases.openPaygoNoManufacturer") }}
      </p>
      <form class="md-layout md-gutter" @submit.prevent="register">
        <div class="md-layout-item md-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.serialNumber }">
            <label for="openpaygo-serial-number">
              {{ $tc("phrases.serialNumber") }}
            </label>
            <md-input
              id="openpaygo-serial-number"
              v-model.trim="registration.serialNumber"
              autocomplete="off"
            />
            <span class="md-error">{{ validationErrors.serialNumber }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.manufacturerId }">
            <label for="openpaygo-manufacturer">
              {{ $tc("words.manufacturer") }}
            </label>
            <md-select
              id="openpaygo-manufacturer"
              v-model="registration.manufacturerId"
            >
              <md-option
                v-for="manufacturer in manufacturers"
                :key="manufacturer.id"
                :value="manufacturer.id"
              >
                {{ manufacturer.name }}
              </md-option>
            </md-select>
            <span class="md-error">{{ validationErrors.manufacturerId }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100">
          <customer-search-select
            v-model="registration.personId"
            :error="validationErrors.personId"
          />
        </div>

        <div class="md-layout-item md-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.applianceId }">
            <label for="openpaygo-appliance">
              {{ $tc("words.appliance") }}
            </label>
            <md-select
              id="openpaygo-appliance"
              v-model="registration.applianceId"
            >
              <md-option
                v-for="appliance in appliances"
                :key="appliance.id"
                :value="appliance.id"
              >
                {{ appliance.name }}
              </md-option>
            </md-select>
            <span class="md-error">{{ validationErrors.applianceId }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.secretKeyHex }">
            <label for="openpaygo-secret-key">
              {{ $tc("phrases.openPaygoSecretKey") }}
            </label>
            <md-input
              id="openpaygo-secret-key"
              v-model.trim="registration.secretKeyHex"
              type="password"
              autocomplete="off"
              spellcheck="false"
            />
            <span class="md-error">{{ validationErrors.secretKeyHex }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.startingCode }">
            <label for="openpaygo-starting-code">
              {{ $tc("phrases.openPaygoStartingCode") }}
            </label>
            <md-input
              id="openpaygo-starting-code"
              v-model="registration.startingCode"
              type="number"
              min="0"
              max="999999999"
              step="1"
            />
            <span class="md-error">{{ validationErrors.startingCode }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.nextCounter }">
            <label for="openpaygo-next-counter">
              {{ $tc("phrases.openPaygoNextCounter") }}
            </label>
            <md-input
              id="openpaygo-next-counter"
              v-model="registration.nextCounter"
              type="number"
              min="0"
              max="100000"
              step="1"
            />
            <span class="md-error">{{ validationErrors.nextCounter }}</span>
          </md-field>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-field :class="{ 'md-invalid': validationErrors.timeDivider }">
            <label for="openpaygo-time-divider">
              {{ $tc("phrases.openPaygoTimeDivider") }}
            </label>
            <md-input
              id="openpaygo-time-divider"
              v-model="registration.timeDivider"
              type="number"
              min="1"
              max="255"
              step="1"
            />
            <span class="md-error">{{ validationErrors.timeDivider }}</span>
          </md-field>
        </div>
      </form>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </md-dialog-content>
    <md-dialog-actions>
      <md-button
        class="md-primary"
        :disabled="loading || !appliances.length || !manufacturers.length"
        @click="register"
      >
        {{ $tc("words.register") }}
      </md-button>
      <md-button :disabled="loading" @click="close">
        {{ $tc("words.cancel") }}
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { ApplianceService } from "@/services/ApplianceService.js"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService.js"
import CustomerSearchSelect from "@/shared/CustomerSearchSelect.vue"

const SHS_APPLIANCE_TYPE_ID = 1

export default {
  name: "OpenPaygoRegistrationModal",
  components: { CustomerSearchSelect },
  mixins: [notify],
  props: {
    show: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      applianceService: new ApplianceService(),
      solarHomeSystemService: new SolarHomeSystemService(),
      loading: false,
      appliances: [],
      manufacturers: [],
      manufacturerOptionsLoaded: false,
      validationErrors: {},
      registration: this.emptyRegistration(),
    }
  },
  computed: {
    dialogVisible: {
      get() {
        return this.show
      },
      set(isVisible) {
        if (!isVisible) this.close()
      },
    },
  },
  watch: {
    show(isVisible) {
      if (!isVisible) {
        this.registration.secretKeyHex = ""
        return
      }
      if (isVisible) {
        this.resetForm()
        this.loadFormOptions()
      }
    },
  },
  beforeDestroy() {
    this.registration.secretKeyHex = ""
  },
  methods: {
    emptyRegistration() {
      return {
        serialNumber: "",
        manufacturerId: null,
        applianceId: null,
        personId: null,
        secretKeyHex: "",
        startingCode: "",
        nextCounter: "",
        timeDivider: "",
      }
    },
    async loadFormOptions() {
      this.loading = true
      this.manufacturerOptionsLoaded = false
      this.manufacturers = []
      this.appliances = []
      try {
        const manufacturers =
          await this.solarHomeSystemService.getOpenPaygoManufacturers()
        this.manufacturers = manufacturers
        this.manufacturerOptionsLoaded = true
        await this.applianceService.getAppliances()
        this.appliances = this.applianceService.list.filter(
          (appliance) => appliance.applianceTypeId === SHS_APPLIANCE_TYPE_ID,
        )
      } catch (error) {
        this.alertNotify("error", error.message)
      } finally {
        this.loading = false
      }
    },
    resetForm() {
      this.registration = this.emptyRegistration()
      this.validationErrors = {}
    },
    validateRegistration() {
      const validationErrors = {}
      const serialNumber = this.registration.serialNumber
      if (!serialNumber) {
        validationErrors.serialNumber = this.$tc(
          "phrases.openPaygoSerialRequired",
        )
      } else if (serialNumber.length < 8 || serialNumber.length > 15) {
        validationErrors.serialNumber = this.$tc(
          "phrases.openPaygoSerialLength",
        )
      }
      if (!this.registration.personId) {
        validationErrors.personId = this.$tc(
          "phrases.openPaygoCustomerRequired",
        )
      }
      if (!this.registration.manufacturerId) {
        validationErrors.manufacturerId = this.$tc(
          "phrases.openPaygoManufacturerRequired",
        )
      }
      if (!this.registration.applianceId) {
        validationErrors.applianceId = this.$tc(
          "phrases.openPaygoApplianceRequired",
        )
      }
      if (!/^[0-9a-fA-F]{32}$/.test(this.registration.secretKeyHex)) {
        validationErrors.secretKeyHex = this.$tc(
          "phrases.openPaygoSecretInvalid",
        )
      }

      const boundedIntegers = [
        ["startingCode", 0, 999999999],
        ["nextCounter", 0, 100000],
        ["timeDivider", 1, 255],
      ]
      for (const [field, minimum, maximum] of boundedIntegers) {
        const value = Number(this.registration[field])
        if (
          String(this.registration[field] ?? "").trim() === "" ||
          !Number.isInteger(value) ||
          value < minimum ||
          value > maximum
        ) {
          const errorKey = `phrases.openPaygo${field[0].toUpperCase()}${field.slice(1)}Invalid`
          validationErrors[field] = this.$tc(errorKey, 1, { minimum, maximum })
        }
      }

      this.validationErrors = validationErrors
      return Object.keys(validationErrors).length === 0
    },
    async register() {
      if (this.loading || !this.validateRegistration()) return

      this.loading = true
      try {
        const createdDevice =
          await this.solarHomeSystemService.registerOpenPaygoDevice({
            ...this.registration,
            startingCode: Number(this.registration.startingCode),
            nextCounter: Number(this.registration.nextCounter),
            timeDivider: Number(this.registration.timeDivider),
          })
        this.registration.secretKeyHex = ""
        this.alertNotify("success", this.$tc("phrases.openPaygoRegistered"))
        this.$emit("created", createdDevice)
      } catch (error) {
        this.alertNotify("error", error.message)
      } finally {
        this.loading = false
      }
    },
    close() {
      if (this.loading) return

      this.registration.secretKeyHex = ""
      this.$emit("close")
    },
  },
}
</script>

<style lang="scss" scoped>
.openpaygo-registration {
  max-width: 56rem;
}
</style>
