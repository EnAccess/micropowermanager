<template>
  <div>
    <md-dialog
      :md-active.sync="showAddShs"
      style="max-width: 60rem; margin: auto; overflow: auto"
    >
      <md-dialog-title>
        <h4
          style="font-size: 1.2rem; margin: 0; border-bottom: solid 1px #dedede"
        >
          {{ $tc("words.shs", 1) }}
        </h4>
      </md-dialog-title>

      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <form
              class="md-layout md-gutter"
              data-vv-scope="shs-add-form"
              @submit.prevent="save"
            >
              <!-- serial numbers -->
              <div class="md-layout-item md-size-100 md-small-size-100">
                <md-chips
                  ref="serialChips"
                  v-model="serialNumbers"
                  md-placeholder="Add another Serial Number..."
                  @md-input="splitCommaInChips"
                >
                  <label>{{ $tc("phrases.serialNumber") }}</label>
                </md-chips>
                <p class="md-caption serial-numbers-hint">
                  Paste a comma-separated list to add multiple serials at once.
                </p>
              </div>

              <!--manufacturer list-->
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('shs-add-form.manufacturer'),
                  }"
                >
                  <label for="manufacturers">
                    {{ $tc("words.manufacturer") }}
                  </label>
                  <md-select
                    v-model="solarHomeSystemService.shs.manufacturerId"
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
                    {{ errors.first("shs-add-form.manufacturer") }}
                  </span>
                </md-field>
              </div>

              <!--appliance list-->
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('shs-add-form.appliance'),
                  }"
                >
                  <label for="appliances">
                    {{ $tc("words.appliance") }}
                  </label>
                  <md-select
                    v-model="solarHomeSystemService.shs.applianceId"
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
                    {{ errors.first("shs-add-form.appliance") }}
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
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { ApplianceService } from "@/services/ApplianceService.js"
import { ManufacturerService } from "@/services/ManufacturerService.js"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService.js"

const MANUFACTURER_TYPE = "shs"
const APPLIANCE_TYPE_ID = 1
const SERIAL_NUMBER_MIN_LENGTH = 8
const SERIAL_NUMBER_MAX_LENGTH = 15

function splitSerialList(text) {
  return text
    .split(",")
    .map((part) => part.trim())
    .filter(Boolean)
}

function appendUnique(target, items) {
  for (const item of items) {
    if (!target.includes(item)) target.push(item)
  }
  return target
}

export default {
  name: "AddSolarHomeSystemModal",
  mixins: [notify, timing],
  props: {
    showAddShs: {
      default: false,
      type: Boolean,
    },
  },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      manufacturerService: new ManufacturerService(),
      applianceService: new ApplianceService(),
      loading: false,
      serialNumbers: [],
    }
  },
  beforeMount() {
    this.manufacturerService.getManufacturers()
    this.applianceService.getAppliances()
  },
  watch: {
    showAddShs(isOpen) {
      if (!isOpen) return
      this.serialNumbers = []
      this.$nextTick(this.attachChipsInputListeners)
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
        (appliance) => appliance.applianceTypeId === APPLIANCE_TYPE_ID,
      )
    },
    serialNumbersError() {
      if (this.serialNumbers.length === 0) return ""
      return this.validateSerialNumbers()
    },
  },
  methods: {
    chipsInputElement() {
      const chipsRoot = this.$refs.serialChips && this.$refs.serialChips.$el
      return chipsRoot ? chipsRoot.querySelector("input") : null
    },
    attachChipsInputListeners() {
      const input = this.chipsInputElement()
      if (!input) return
      input.addEventListener("paste", this.onSerialPaste)
      input.addEventListener("input", this.onSerialInput)
    },
    onSerialInput(event) {
      if (!event.target.value.includes(",")) return
      const parts = splitSerialList(event.target.value)
      this.serialNumbers = appendUnique([...this.serialNumbers], parts)
      event.target.value = ""
    },
    onSerialPaste(event) {
      const pasted = event.clipboardData && event.clipboardData.getData("text")
      if (!pasted || !pasted.includes(",")) return
      event.preventDefault()
      this.serialNumbers = appendUnique(
        [...this.serialNumbers],
        splitSerialList(pasted),
      )
      event.target.value = ""
    },
    splitCommaInChips(chips) {
      const hasCommaChip = chips.some(
        (chip) => typeof chip === "string" && chip.includes(","),
      )
      if (!hasCommaChip) return
      const next = []
      for (const chip of chips) {
        const parts =
          typeof chip === "string" && chip.includes(",")
            ? splitSerialList(chip)
            : [chip]
        appendUnique(next, parts)
      }
      this.serialNumbers = next
    },
    validateSerialNumbers() {
      if (this.serialNumbers.length === 0) {
        return "At least one Serial Number is required."
      }
      const seen = new Set()
      for (const serial of this.serialNumbers) {
        if (
          serial.length < SERIAL_NUMBER_MIN_LENGTH ||
          serial.length > SERIAL_NUMBER_MAX_LENGTH
        ) {
          return `Each Serial Number must be ${SERIAL_NUMBER_MIN_LENGTH}–${SERIAL_NUMBER_MAX_LENGTH} characters: "${serial}"`
        }
        if (seen.has(serial)) return `Duplicate Serial Number: "${serial}"`
        seen.add(serial)
      }
      return ""
    },
    async save() {
      const serialError = this.validateSerialNumbers()
      const formValid = await this.$validator.validateAll("shs-add-form")
      if (serialError) {
        this.alertNotify("error", serialError)
        return
      }
      if (!formValid) {
        this.alertNotify("error", "Please fill all required fields.")
        return
      }

      this.solarHomeSystemService.shs.serialNumbers = this.serialNumbers
      this.loading = true
      try {
        const createdShs =
          await this.solarHomeSystemService.createSolarHomeSystem()
        const count = this.serialNumbers.length
        this.alertNotify(
          "success",
          this.$tc("phrases.newShs") + (count > 1 ? ` (${count})` : ""),
        )
        this.$emit("created", createdShs)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    cancel() {
      this.$emit("hideAddShs")
    },
  },
  beforeDestroy() {
    const input = this.chipsInputElement()
    if (!input) return
    input.removeEventListener("paste", this.onSerialPaste)
    input.removeEventListener("input", this.onSerialInput)
  },
}
</script>

<style lang="scss" scoped>
.serial-numbers-error {
  color: #ff1744;
  margin-top: 4px;
}

.serial-numbers-hint {
  color: rgba(0, 0, 0, 0.54);
  margin-top: 4px;
}
</style>
