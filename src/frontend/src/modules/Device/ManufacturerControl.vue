<template>
  <widget :title="$tc('phrases.deviceMapping')" color="primary">
    <md-list class="md-double-line">
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("words.status") }}</span>
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
    </md-list>

    <div class="verify-action">
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
  </widget>
</template>

<script>
import { mappingStatus } from "@/mixins/mappingStatus.js"
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { DeviceService } from "@/services/DeviceService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "ManufacturerControl",
  mixins: [notify, timing, mappingStatus],
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
      showDialog: false,
      result: null,
      currentStatus: this.status,
      currentCheckedAt: this.checkedAt,
    }
  },
  computed: {
    displayStatus() {
      return this.currentStatus || "unknown"
    },
    checkedAtDisplay() {
      return this.currentCheckedAt
        ? this.timeForTimeZone(this.currentCheckedAt)
        : null
    },
  },
  methods: {
    isExpandable(value) {
      return value !== null && typeof value === "object"
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
    statusFromResult(result) {
      if (!result.supported) return "unsupported"
      return result.mapped ? "mapped" : "not_mapped"
    },
  },
}
</script>

<style lang="scss" scoped>
.verify-action {
  display: flex;
  justify-content: flex-start;
  padding: 8px 16px 16px;
}

.md-button.md-raised.verify-button:not([disabled]) {
  background-color: $brand-accent;
  color: $brand-white;

  ::v-deep .md-icon {
    color: $brand-white;
  }
}
</style>
