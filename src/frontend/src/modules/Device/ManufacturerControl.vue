<template>
  <widget :title="$tc('phrases.manufacturerControl')" color="primary">
    <md-list class="md-double-line">
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.deviceMapping") }}</span>
          <span>{{ mappingStatusLabel(displayStatus) }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item v-if="checkedAtDisplay">
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.lastChecked") }}</span>
          <span>{{ checkedAtDisplay }}</span>
        </div>
      </md-list-item>
      <md-list-item v-if="capabilities.tokenGenerationBlockedReason">
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.generateToken") }}</span>
          <span>{{ capabilities.tokenGenerationBlockedReason }}</span>
        </div>
      </md-list-item>
    </md-list>

    <div class="control-actions">
      <md-button
        class="md-raised verify-button"
        :disabled="verifying"
        @click="verify"
      >
        <md-icon>fact_check</md-icon>
        {{ $tc("phrases.verifyDeviceMapping") }}
        <md-tooltip md-direction="top">
          {{ $tc("phrases.verifyDeviceMappingHelp") }}
        </md-tooltip>
      </md-button>

      <md-button
        v-if="canControl && availableTokenTypes.length"
        class="md-raised verify-button"
        :disabled="generating"
        @click="openTokenDialog"
      >
        <md-icon>vpn_key</md-icon>
        {{ $tc("phrases.generateToken") }}
        <md-tooltip md-direction="top">
          {{ $tc("phrases.generateTokenHelp") }}
        </md-tooltip>
      </md-button>
    </div>

    <md-dialog :md-active.sync="showDialog">
      <md-dialog-title>{{ $tc("phrases.deviceMapping") }}</md-dialog-title>
      <md-dialog-content>
        <md-progress-bar md-mode="indeterminate" v-if="verifying" />
        <div v-else-if="result">
          <md-empty-state
            v-if="!result.supported"
            md-icon="info"
            :md-label="$tc('phrases.verificationUnavailable')"
            :md-description="
              $tc('phrases.verificationUnavailableDescription', 1, {
                manufacturer: manufacturerName || $tc('words.manufacturer'),
              })
            "
          />
          <md-list v-else-if="result.mapped" class="md-double-line">
            <md-list-item>
              <md-icon style="color: green">check_circle</md-icon>
              <div class="md-list-item-text">
                <span>{{ $tc("words.status") }}</span>
                <span>
                  {{
                    $tc("phrases.deviceMapped", 1, {
                      manufacturer: manufacturerName,
                    })
                  }}
                </span>
              </div>
            </md-list-item>
            <template v-for="(value, key) in result.device">
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
                serial: serialNumber,
                manufacturer: manufacturerName || $tc('words.manufacturer'),
              })
            "
          />
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-primary" @click="showDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>

    <md-dialog :md-active.sync="showTokenDialog">
      <md-dialog-title>{{ $tc("phrases.generateToken") }}</md-dialog-title>
      <md-dialog-content>
        <md-progress-bar md-mode="indeterminate" v-if="generating" />

        <div v-if="generatedToken">
          <md-list class="md-double-line">
            <md-list-item>
              <md-icon style="color: green">check_circle</md-icon>
              <div class="md-list-item-text">
                <span>{{ $tc("words.token") }}</span>
                <span>{{ formatToken(generatedToken.token) }}</span>
              </div>
            </md-list-item>
            <template v-if="generatedToken.tokenAmount">
              <md-divider></md-divider>
              <md-list-item>
                <div class="md-list-item-text">
                  <span>{{ $tc("phrases.issuedCredit") }}</span>
                  <span>
                    {{ generatedToken.tokenAmount }}
                    {{ unitLabel(generatedToken.tokenUnit) }}
                  </span>
                </div>
              </md-list-item>
            </template>
          </md-list>
          <p class="rounding-note" v-if="creditWasRounded">
            {{
              $tc("phrases.issuedCreditDiffers", 1, {
                requested: `${amount} ${unitLabel(unit)}`,
              })
            }}
          </p>
          <p class="dialog-hint" v-if="tokenType !== 'credit'">
            {{ $tc(`phrases.${tokenType}TokenIssued`) }}
          </p>
        </div>

        <div v-else-if="!generating">
          <md-field v-if="availableTokenTypes.length > 1">
            <label>{{ $tc("phrases.tokenPurpose") }}</label>
            <md-select v-model="tokenType">
              <md-option
                v-for="type in availableTokenTypes"
                :key="type"
                :value="type"
              >
                {{ tokenTypeLabel(type) }}
              </md-option>
            </md-select>
          </md-field>

          <template v-if="tokenType === 'credit'">
            <p class="dialog-hint">{{ $tc("phrases.generateTokenHint") }}</p>
            <md-field>
              <label>{{ $tc("words.amount") }}</label>
              <md-input v-model="amount" type="number" step="any" min="0" />
            </md-field>
            <md-field>
              <label>{{ $tc("phrases.amountUnit") }}</label>
              <md-select v-model="unit">
                <md-option value="currency">
                  {{ unitLabel("currency") }}
                </md-option>
                <md-option
                  v-if="capabilities.creditUnit"
                  :value="capabilities.creditUnit"
                >
                  {{ unitLabel(capabilities.creditUnit) }}
                </md-option>
              </md-select>
            </md-field>
          </template>

          <p class="control-warning" v-else>
            <md-icon>warning</md-icon>
            <span>{{ $tc(`phrases.${tokenType}DeviceConfirm`) }}</span>
          </p>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          v-if="!generatedToken"
          class="md-primary"
          :disabled="generating || (tokenType === 'credit' && !amount)"
          @click="generate"
        >
          {{ $tc("words.generate", 1) }}
        </md-button>
        <md-button class="md-primary" @click="showTokenDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </widget>
</template>

<script>
import { currency } from "@/mixins/currency.js"
import { mappingStatus } from "@/mixins/mappingStatus.js"
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { token } from "@/mixins/token.js"
import { DeviceService } from "@/services/DeviceService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "ManufacturerControl",
  mixins: [notify, timing, mappingStatus, token, currency],
  components: { Widget },
  props: {
    deviceId: {
      type: [Number, String],
      required: true,
    },
    serialNumber: {
      type: [Number, String],
      default: null,
    },
    manufacturerName: {
      type: String,
      default: null,
    },
    status: {
      type: String,
      default: "unknown",
    },
    checkedAt: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      deviceService: new DeviceService(),
      verifying: false,
      generating: false,
      showDialog: false,
      showTokenDialog: false,
      result: null,
      capabilities: {
        tokenGeneration: false,
        unlockToken: false,
        resetToken: false,
        creditUnit: null,
        tokenGenerationBlockedReason: null,
      },
      generatedToken: null,
      tokenType: "credit",
      amount: null,
      unit: "currency",
      currentStatus: this.status,
      currentCheckedAt: this.checkedAt,
    }
  },
  computed: {
    canControl() {
      return this.$can("transactions")
    },
    displayStatus() {
      return this.currentStatus || "unknown"
    },
    checkedAtDisplay() {
      return this.currentCheckedAt
        ? this.timeForTimeZone(this.currentCheckedAt)
        : null
    },
    creditWasRounded() {
      if (this.tokenType !== "credit") return false
      if (!this.generatedToken || this.unit === "currency") return false
      return Number(this.generatedToken.tokenAmount) !== Number(this.amount)
    },
    availableTokenTypes() {
      return [
        ["credit", this.capabilities.tokenGeneration],
        ["unlock", this.capabilities.unlockToken],
        ["reset", this.capabilities.resetToken],
      ]
        .filter(([, supported]) => supported)
        .map(([type]) => type)
    },
  },
  created() {
    if (this.canControl) this.loadCapabilities()
  },
  methods: {
    isExpandable(value) {
      return value !== null && typeof value === "object"
    },
    async loadCapabilities() {
      try {
        this.capabilities = await this.deviceService.getCapabilities(
          this.deviceId,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async verify() {
      this.verifying = true
      this.result = null
      this.showDialog = true
      try {
        this.result = await this.deviceService.getManufacturerInfo(
          this.deviceId,
        )
        this.currentStatus = this.statusFromResult(this.result)
        this.currentCheckedAt = new Date().toISOString()
      } catch (e) {
        this.showDialog = false
        this.alertNotify("error", e.message)
      } finally {
        this.verifying = false
      }
    },
    openTokenDialog() {
      this.tokenType = this.availableTokenTypes[0]
      this.generatedToken = null
      this.amount = null
      this.unit = "currency"
      this.showTokenDialog = true
    },
    tokenTypeLabel(tokenType) {
      const labels = {
        credit: "phrases.creditToken",
        unlock: "phrases.unlockDevice",
        reset: "phrases.resetDevice",
      }
      return this.$tc(labels[tokenType])
    },
    async generate() {
      this.generating = true
      try {
        this.generatedToken =
          this.tokenType === "credit"
            ? await this.deviceService.generateToken(
                this.deviceId,
                Number(this.amount),
                this.unit,
              )
            : await this.deviceService.generateControlToken(
                this.deviceId,
                this.tokenType,
              )
        this.$emit("tokenGenerated", this.generatedToken)
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.generating = false
      }
    },
    statusFromResult(result) {
      if (!result.supported) return "unsupported"
      return result.mapped ? "mapped" : "not_mapped"
    },
  },
}
</script>

<style lang="scss" scoped>
.control-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  padding: 8px 16px 16px;
}

.control-warning {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin: 0;
  // The dialog sizes to its content, so the prose is what caps its width.
  max-width: 420px;

  ::v-deep .md-icon {
    color: $brand-secondary-dark;
    margin: 0;
    flex-shrink: 0;
  }
}

.dialog-hint {
  margin-bottom: 8px;
  max-width: 420px;
}

.rounding-note {
  margin-top: 8px;
  font-style: italic;
}

.md-button.md-raised.verify-button:not([disabled]) {
  background-color: $brand-accent;
  color: $brand-white;

  ::v-deep .md-icon {
    color: $brand-white;
  }
}
</style>
