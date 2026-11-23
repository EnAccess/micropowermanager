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
      <md-list-item v-if="canControl">
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.vendingPlugin") }}</span>
          <span>{{ currentVendingPluginName }}</span>
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
        v-if="canControl"
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

      <md-button
        v-if="canControl && vendingPluginOptions.length > 1"
        class="md-raised verify-button"
        @click="openVendingPluginDialog"
      >
        <md-icon>swap_horiz</md-icon>
        {{ $tc("phrases.changeVendingPlugin") }}
        <md-tooltip md-direction="top">
          {{ $tc("phrases.changeVendingPluginHelp") }}
        </md-tooltip>
      </md-button>
    </div>

    <md-dialog :md-active.sync="showVendingPluginDialog">
      <md-dialog-title>
        {{ $tc("phrases.changeVendingPlugin") }}
      </md-dialog-title>
      <md-dialog-content>
        <md-progress-bar md-mode="indeterminate" v-if="savingVendingPlugin" />
        <p class="dialog-hint">{{ $tc("phrases.changeVendingPluginHelp") }}</p>
        <md-field>
          <label>{{ $tc("phrases.vendingPlugin") }}</label>
          <md-select v-model="selectedVendingPluginId">
            <md-option
              v-for="plugin in vendingPluginOptions"
              :key="plugin.id"
              :value="plugin.id"
            >
              {{ plugin.name }}
            </md-option>
          </md-select>
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          class="md-primary"
          :disabled="
            savingVendingPlugin ||
            selectedVendingPluginId === currentVendingPluginId
          "
          @click="saveVendingPlugin"
        >
          {{ $tc("words.save") }}
        </md-button>
        <md-button class="md-primary" @click="showVendingPluginDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>

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
          <p class="dialog-hint" v-if="tokenType !== 'add_credit'">
            {{ $tc(`phrases.tokenTypes.${tokenType}.issued`) }}
          </p>
        </div>

        <div v-else-if="!generating">
          <md-field>
            <label>{{ $tc("phrases.tokenPurpose") }}</label>
            <md-select v-model="tokenType">
              <md-option
                v-for="type in capabilities.tokenTypes"
                :key="type"
                :value="type"
              >
                {{ $tc(`phrases.tokenTypes.${type}.label`) }}
              </md-option>
              <md-option
                v-if="capabilities.tokenGenerationBlockedReason"
                value="add_credit"
                disabled
              >
                {{ $tc("phrases.tokenTypes.add_credit.label") }}
              </md-option>
            </md-select>
          </md-field>
          <p
            class="dialog-hint"
            v-if="capabilities.tokenGenerationBlockedReason"
          >
            {{ capabilities.tokenGenerationBlockedReason }}
          </p>

          <template v-if="tokenType === 'add_credit'">
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

          <p class="control-warning" v-else-if="tokenType">
            <md-icon>warning</md-icon>
            <span>{{ $tc(`phrases.tokenTypes.${tokenType}.confirm`) }}</span>
          </p>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          v-if="!generatedToken"
          class="md-primary"
          :disabled="
            generating || !tokenType || (tokenType === 'add_credit' && !amount)
          "
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
        tokenTypes: [],
        creditUnit: null,
        tokenGenerationBlockedReason: null,
      },
      generatedToken: null,
      tokenType: null,
      amount: null,
      unit: "currency",
      currentStatus: this.status,
      currentCheckedAt: this.checkedAt,
      currentVendingPluginId: null,
      vendingPluginOptions: [],
      selectedVendingPluginId: null,
      showVendingPluginDialog: false,
      savingVendingPlugin: false,
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
      if (this.tokenType !== "add_credit") return false
      if (!this.generatedToken || this.unit === "currency") return false
      return Number(this.generatedToken.tokenAmount) !== Number(this.amount)
    },
    currentVendingPluginName() {
      const current = this.vendingPluginOptions.find(
        (plugin) => plugin.id === this.currentVendingPluginId,
      )
      return current ? current.name : this.$tc("phrases.noVendingPlugin")
    },
  },
  created() {
    if (!this.canControl) return
    this.loadCapabilities()
    this.loadVendingPlugins()
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
    async loadVendingPlugins() {
      try {
        const { vendingPluginId, options } =
          await this.deviceService.getVendingPlugins(this.deviceId)
        this.currentVendingPluginId = vendingPluginId
        this.vendingPluginOptions = options
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    openVendingPluginDialog() {
      this.selectedVendingPluginId = this.currentVendingPluginId
      this.showVendingPluginDialog = true
    },
    async saveVendingPlugin() {
      this.savingVendingPlugin = true
      try {
        const device = await this.deviceService.updateVendingPlugin(
          this.deviceId,
          this.selectedVendingPluginId,
        )
        this.currentVendingPluginId = device.vendingPluginId
        this.showVendingPluginDialog = false
        this.alertNotify("success", this.$tc("phrases.vendingPluginChanged"))
        await this.loadCapabilities()
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.savingVendingPlugin = false
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
      this.tokenType = null
      this.generatedToken = null
      this.amount = null
      this.unit = "currency"
      this.showTokenDialog = true
    },
    async generate() {
      this.generating = true
      try {
        this.generatedToken =
          this.tokenType === "add_credit"
            ? await this.deviceService.generateToken(
                this.deviceId,
                Number(this.amount),
                this.unit,
              )
            : await this.deviceService.generateControlToken(
                this.deviceId,
                this.tokenType,
              )
        this.$emit("token-generated", this.generatedToken)
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
