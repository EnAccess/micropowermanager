<template>
  <div>
    <widget
      v-if="toggleNewType"
      id="add-new-meter-type"
      :title="$tc('phrases.newMeterType')"
      color="red"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter md-size-100">
            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.maxCurrent')),
                }"
              >
                <label>{{ $tc("phrases.maxCurrent") }}</label>
                <md-input
                  id="max_current"
                  :name="$tc('phrases.maxCurrent')"
                  v-model="meterType.max_current"
                  v-validate="'required|numeric'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first($tc("phrases.maxCurrent")) }}
                </span>
                <span class="md-suffix">Amper</span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.phase')),
                }"
              >
                <label>{{ $tc("words.phase") }}</label>
                <md-input
                  id="phase"
                  :name="$tc('words.phase')"
                  v-model="meterType.phase"
                  v-validate="'required|numeric'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first($tc("words.phase")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-10 md-small-size-50">
              <span class="md-subheader">
                <md-checkbox v-model="meterType.online" class="md-primary">
                  {{ $tc("words.online") }}
                </md-checkbox>
              </span>
            </div>
          </div>
        </md-card-content>
        <md-card-actions>
          <md-button
            class="md-primary md-dense md-raised"
            @click="saveMeterType"
          >
            {{ $tc("words.save") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <widget
      id="meter-types-list"
      :title="$tc('phrases.meterTypes')"
      :button="true"
      :subscriber="subscriber"
      :buttonText="$tc('phrases.newMeterType')"
      @widgetAction="showNewType"
      color="green"
    >
      <md-table>
        <md-table-row>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
          <md-table-head>
            {{ $tc("phrases.maxCurrent") }}
          </md-table-head>
          <md-table-head>
            {{ $tc("words.connectivity") }}
          </md-table-head>
        </md-table-row>
        <md-table-row v-for="(type, index) in meterTypesList" :key="index">
          <md-table-cell>{{ type.name }}</md-table-cell>
          <md-table-cell>{{ type.max_current }}</md-table-cell>
          <md-table-cell>
            <md-icon>
              {{ type.online === 1 ? "check_box" : "check_box_outline_blank" }}
            </md-icon>
            <span>
              {{
                type.online === 1 ? $tc("words.online") : $tc("words.offline")
              }}
            </span>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { MeterTypeService } from "@/services/MeterTypeService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"
export default {
  name: "MeterTypes",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      meterTypeService: new MeterTypeService(),
      toggleNewType: false,
      subscriber: "meter-type",
      meterType: {
        max_current: null,
        phase: null,
        online: null,
      },
      meterTypesList: null,
    }
  },
  mounted() {
    this.getMeterTypes()
  },
  methods: {
    showNewType() {
      // https://github.com/logaretm/vee-validate/issues/1828#issuecomment-631808596
      this.$validator.pause()
      this.$nextTick(() => {
        this.toggleNewType = !this.toggleNewType
        this.$validator.resume()
      })
    },
    async saveMeterType() {
      let validation = await this.$validator.validateAll()
      if (!validation) {
        return
      }
      try {
        this.meterTypesList = await this.meterTypeService.createMeterType(
          this.meterType,
        )
        this.showNewType()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.meterTypesList.length,
        )
        this.meterType.max_current = null
        this.meterType.phase = null
        this.meterType.online = null
        this.alertNotify("success", this.$tc("phrases.newMeterType", 2))
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getMeterTypes() {
      try {
        this.meterTypesList = await this.meterTypeService.getMeterTypes()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.meterTypesList.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
