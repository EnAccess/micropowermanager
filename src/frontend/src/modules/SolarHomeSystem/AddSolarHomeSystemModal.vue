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
            <form class="md-layout md-gutter" data-vv-scope="shs-add-form">
              <!-- serial number -->
              <div class="md-layout-item md-size-100 md-small-size-100">
                <md-chips
                  ref="serialNumberChips"
                  id="serial_number"
                  :class="{ 'md-invalid': serialNumberError !== null }"
                  v-model="serialNumbers"
                  :md-check-duplicated="true"
                  @md-insert="handleInsertedSerial"
                  @md-click="editSerialChip"
                  @paste.native="handleSerialPaste"
                >
                  <label for="serial_number">
                    {{ $tc("phrases.serialNumber") }}
                  </label>
                  <template slot="md-chip" slot-scope="{ chip }">
                    <span
                      :class="{ 'invalid-serial-chip': isInvalidSerialNumber(chip) }"
                    >
                      {{ chip }}
                    </span>
                  </template>
                </md-chips>
                <small class="serial-number-hint">
                  Paste comma-separated serial numbers (example:
                  12345678,87654321). Click a chip to edit it.
                </small>
                <span class="md-error serial-number-error" v-if="serialNumberError">
                  <template v-if="invalidSerialNumbers.length > 0">
                    Invalid serial number(s):
                    <span class="invalid-serial-highlight">
                      {{ invalidSerialNumbers.join(", ") }}
                    </span>
                    . Each serial must contain 8 to 11 characters.
                  </template>
                  <template v-else>
                    {{ serialNumberError }}
                  </template>
                </span>
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

//these are fixed values in the database
const MANUFACTURER_TYPE = "shs"
const APPLIANCE_TYPE_ID = 1

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
      serialNumberError: null,
      invalidSerialNumbers: [],
      editingSerialChipIndex: null,
    }
  },
  beforeMount() {
    this.manufacturerService.getManufacturers()
    this.applianceService.getAppliances()
  },
  methods: {
    clearSerialInput() {
      this.serialNumbers = []
      this.serialNumberError = null
      this.invalidSerialNumbers = []
      this.editingSerialChipIndex = null

      const serialNumberChips = this.$refs.serialNumberChips
      if (serialNumberChips) {
        serialNumberChips.inputValue = ""
      }
    },
    commitPendingSerialInput() {
      const serialNumberChips = this.$refs.serialNumberChips
      if (!serialNumberChips || !serialNumberChips.inputValue) {
        return
      }

      if (serialNumberChips.inputValue.trim().length > 0) {
        serialNumberChips.insertChip({ target: null })
      }
    },
    parseSerialNumbers(value) {
      return value
        .split(/[\n,]/)
        .map((serialNumber) => serialNumber.trim())
        .filter((serialNumber) => serialNumber.length > 0)
    },
    appendSerialNumbers(serialNumbers, insertAtIndex = null) {
      const serialNumbersToInsert = serialNumbers.filter(
        (serialNumber) => !this.serialNumbers.includes(serialNumber),
      )

      if (serialNumbersToInsert.length === 0) {
        this.serialNumberError = null
        this.invalidSerialNumbers = []
        return
      }

      if (
        Number.isInteger(insertAtIndex) &&
        insertAtIndex >= 0 &&
        insertAtIndex <= this.serialNumbers.length
      ) {
        this.serialNumbers.splice(insertAtIndex, 0, ...serialNumbersToInsert)
      } else {
        this.serialNumbers.push(...serialNumbersToInsert)
      }

      this.serialNumberError = null
      this.invalidSerialNumbers = []
    },
    isInvalidSerialNumber(serialNumber) {
      return this.invalidSerialNumbers.includes(serialNumber)
    },
    editSerialChip(serialNumber, chipIndex) {
      if (typeof serialNumber !== "string") {
        return
      }

      this.commitPendingSerialInput()
      this.serialNumberError = null
      this.invalidSerialNumbers = []

      const index = Number.isInteger(chipIndex)
        ? chipIndex
        : this.serialNumbers.indexOf(serialNumber)

      if (index === -1) {
        return
      }

      this.editingSerialChipIndex = index
      this.serialNumbers.splice(index, 1)

      const serialNumberChips = this.$refs.serialNumberChips
      if (!serialNumberChips) {
        return
      }

      serialNumberChips.inputValue = serialNumber
      this.$nextTick(() => {
        if (serialNumberChips.$refs.input && serialNumberChips.$refs.input.$el) {
          serialNumberChips.$refs.input.$el.focus()
        }
      })
    },
    handleSerialPaste(event) {
      const clipboardData = event.clipboardData || window.clipboardData
      if (!clipboardData) {
        return
      }

      const pastedValue = clipboardData.getData("text")
      if (typeof pastedValue !== "string" || pastedValue.trim().length === 0) {
        return
      }

      event.preventDefault()
      const insertAtIndex = this.editingSerialChipIndex
      this.editingSerialChipIndex = null
      this.appendSerialNumbers(this.parseSerialNumbers(pastedValue), insertAtIndex)
    },
    handleInsertedSerial(insertedSerialNumber) {
      if (typeof insertedSerialNumber !== "string") {
        return
      }

      const insertAtIndex = this.editingSerialChipIndex
      this.editingSerialChipIndex = null

      const parsedSerialNumbers = this.parseSerialNumbers(insertedSerialNumber)
      if (
        parsedSerialNumbers.length === 1 &&
        parsedSerialNumbers[0] === insertedSerialNumber.trim()
      ) {
        if (!Number.isInteger(insertAtIndex)) {
          this.serialNumberError = null
          return
        }

        const insertedChipIndex = this.serialNumbers.lastIndexOf(
          insertedSerialNumber,
        )
        if (insertedChipIndex !== -1) {
          this.serialNumbers.splice(insertedChipIndex, 1)
        }

        this.appendSerialNumbers(parsedSerialNumbers, insertAtIndex)
        this.serialNumberError = null
        return
      }

      const insertedChipIndex = this.serialNumbers.lastIndexOf(
        insertedSerialNumber,
      )
      if (insertedChipIndex !== -1) {
        this.serialNumbers.splice(insertedChipIndex, 1)
      }

      this.appendSerialNumbers(parsedSerialNumbers, insertAtIndex)
    },
    validateSerialNumbers() {
      if (this.serialNumbers.length === 0) {
        this.serialNumberError = "Please add at least one serial number."
        this.invalidSerialNumbers = []
        return false
      }

      this.invalidSerialNumbers = this.serialNumbers.filter(
        (serialNumber) => serialNumber.length < 8 || serialNumber.length > 11,
      )

      if (this.invalidSerialNumbers.length > 0) {
        this.serialNumberError = "Invalid serial number format."
        return false
      }

      this.serialNumberError = null
      this.invalidSerialNumbers = []
      return true
    },
    async save() {
      this.commitPendingSerialInput()
      const validator = await this.$validator.validateAll("shs-add-form")
      const hasValidSerialNumbers = this.validateSerialNumbers()

      if (validator && hasValidSerialNumbers) {
        this.loading = true
        try {
          const createdShs =
            await this.solarHomeSystemService.createSolarHomeSystems(
              this.serialNumbers,
            )
          this.clearSerialInput()
          this.alertNotify("success", this.$tc("phrases.newShs"))
          this.$emit("created", createdShs)
        } catch (e) {
          this.alertNotify("error", e.message)
        }
        this.loading = false
      }
    },
    cancel() {
      this.clearSerialInput()
      this.$emit("hideAddShs")
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
  },
}
</script>

<style scoped lang="scss">
.serial-number-hint {
  display: block;
  margin-top: 0.25rem;
  color: rgba(0, 0, 0, 0.54);
}

.serial-number-error {
  display: block;
  margin-top: 0.25rem;
}

.invalid-serial-highlight,
.invalid-serial-chip {
  color: #c62828;
  font-weight: 600;
}
</style>
