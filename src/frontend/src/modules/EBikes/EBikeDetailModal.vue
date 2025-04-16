<template>
  <div>
    <md-dialog
      :md-active.sync="showEBikeDetail"
      style="max-width: 60rem; margin: auto; overflow: auto"
    >
      <md-dialog-title>
        <h4
          style="font-size: 1.2rem; margin: 0; border-bottom: solid 1px #dedede"
        >
          {{ $tc("words.e_bike", 1) }} - {{ eBike.serialNumber }}
        </h4>
      </md-dialog-title>

      <md-dialog-content
        style="overflow-y: auto"
        class="md-layout-item md-size-100"
      >
        <div
          v-if="eBike.serialNumber"
          class="md-layout md-gutter"
          style="margin-top: 2rem"
        >
          <div class="md-layout-item md-size-50">
            <box
              :box-color="'blue'"
              :center-text="true"
              :header-text="$tc('words.mileage')"
              :sub-text="eBike.mileage"
              :box-icon="'merge'"
            />
          </div>
          <div class="md-layout-item md-size-50">
            <box
              :box-color="'orange'"
              :center-text="true"
              :header-text="$tc('words.speed')"
              :sub-text="eBike.speed"
              :box-icon="'speed'"
            />
          </div>

          <div
            class="md-layout-item md-size-100 md-layout md-gutter information-cell"
            style="margin-top: 2rem"
          >
            <div class="md-layout-item md-size-50">
              <div class="txt-bold-and-big">
                {{ $tc("phrases.manufacturerName") }}:
                <span class="txt-color-yellow">
                  {{ eBike.manufacturer.name }}
                </span>
              </div>
            </div>
            <div class="md-layout-item md-size-50">
              <div class="txt-bold-and-big">
                {{ $tc("phrases.modelName") }}:
                <span :class="'txt-color-green txt-description'">
                  {{ eBike.appliance.name }}
                </span>
              </div>
            </div>
          </div>
          <div
            class="md-layout-item md-size-100 md-layout md-gutter information-cell"
          >
            <div class="md-layout-item md-size-50">
              <div class="txt-bold-and-big">
                {{ $tc("phrases.batteryLevel") }}:
                <span :class="'txt-color-red txt-description'">
                  {{ eBike.batteryLevel }}
                </span>
              </div>
            </div>
            <div class="md-layout-item md-size-50">
              <div class="txt-bold-and-big">
                {{ $tc("phrases.batteryVoltage") }}:
                <span :class="'txt-color-red txt-description'">
                  {{ eBike.batteryVoltage }}
                </span>
              </div>
            </div>
          </div>
          <div
            class="md-layout-item md-size-100 md-layout md-gutter information-cell"
          >
            <div class="md-layout-item md-size-50">
              <div class="txt-bold-and-big">
                {{ $tc("phrases.lastDataReceived") }}:
                <span :class="'txt-color-green txt-description'">
                  {{ timeForTimeZone(eBike.receiveTime) }}
                </span>
              </div>
            </div>
            <div class="md-layout-item md-size-50">
              <div
                class="txt-bold-and-big"
                style="right: 0; position: absolute; margin-right: 16rem"
              >
                <md-switch
                  v-model="statusOn"
                  class="data-stream-switch"
                  :disabled="loading"
                >
                  <span v-if="eBike.statusOn">
                    {{ $tc("words.lock") }}
                  </span>
                  <span v-else>
                    {{ $tc("words.unlock") }}
                  </span>
                </md-switch>
              </div>
            </div>
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
import { notify, timing } from "@/mixins"
import { EBikeService } from "@/services/EBikeService"
import Box from "@/shared/Box.vue"

export default {
  name: "EBikeDetailModal",
  components: { Box },
  mixins: [notify, timing],
  props: {
    showEBikeDetail: {
      default: false,
      type: Boolean,
    },
    eBike: {
      required: true,
    },
  },
  data() {
    return {
      eBikeService: new EBikeService(),
      loading: false,
      switching: false,
      statusOn: null,
    }
  },
  mounted() {
    this.statusOn = this.eBike.statusOn
  },
  methods: {
    cancel() {
      this.$emit("hideEBikeDetail")
    },
    async save() {
      try {
        this.loading = true
        await this.eBikeService.switchEBike({
          serialNumber: this.eBike.serialNumber,
          status: this.statusOn,
          manufacturerName: this.eBike.manufacturer.name,
        })
        this.alertNotify(
          "success",
          this.$tc("messages.successfullyUpdated", {
            item: this.$tc("words.e_bike", 1),
          }),
        )
      } catch (e) {
        this.statusOn = !this.statusOn
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
  },
}
</script>

<style lang="css" scoped>
.txt-color-green {
  color: green;
}

.txt-color-red {
  color: red;
}

.txt-color-yellow {
  color: #cccc05;
}

.txt-bold-and-big {
  font-size: 1rem;
  font-weight: bolder;
}

.txt-description {
  font-size: 0.8rem;
}

.information-cell {
  min-height: 3rem;
  margin-top: 0.5rem;
}

.data-stream-switch {
  margin-left: 3rem !important;
}
</style>
