<template>
  <div class="row">
    <widget v-if="newLabel" :title="$tc('phrases.newCategory')" color="red">
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <md-input
                  type="text"
                  v-model="ticketLabelService.newLabelName"
                  :placeholder="$tc('words.name')"
                  :name="$tc('words.name')"
                  id="name"
                  v-validate="'required|min:3'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.selectColor')),
                }"
              >
                <label>{{ $tc("phrases.selectColor") }}</label>
                <md-select
                  v-model="ticketLabelService.currentColor"
                  :name="$tc('phrases.selectColor')"
                  id="color"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="(index, colorName) in ticketLabelService.colors"
                    :value="colorName"
                    :key="colorName"
                  >
                    {{ colorName }}
                    <span
                      class="colored-box"
                      style="margin-left: 1rem; max-width: 100px"
                      :style="{
                        backgroundColor: ticketLabelService.colors[colorName],
                      }"
                    ></span>
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("phrases.selectColor")) }}
                </span>
              </md-field>
            </div>
          </div>
          <div class="md-layout md-subheader">
            <md-checkbox
              v-model="ticketLabelService.outSourcing"
              class="form-control"
              id="outsourcing"
            >
              {{ $tc("words.outsourcing") }}
            </md-checkbox>
          </div>
          <div class="md-layout">
            <span class="md-subheader">
              {{ $tc("phrases.ticketLabels", 1) }}
            </span>
          </div>
          <div class="md-layout">
            <span class="md-subheader">
              {{
                $tc("phrases.ticketLabels", 2, {
                  email: " ako@inensus.com",
                })
              }}
            </span>
          </div>
        </md-card-content>
        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-raised md-primary" @click="saveLabel">
            {{ $tc("words.save") }}
          </md-button>
          <md-button
            class="md-raised md-accent"
            @click="
              () => {
                newLabel = false
              }
            "
          >
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>

    <widget
      :title="$tc('phrases.ticketCategories')"
      :button="true"
      :button-text="$tc('phrases.newCategory')"
      @widgetAction="
        () => {
          newLabel = true
        }
      "
      color="green"
      :subscriber="subscriber"
    >
      <md-card>
        <md-card-content>
          <md-table>
            <md-table-row>
              <md-table-head v-for="(item, index) in headers" :key="index">
                {{ item }}
              </md-table-head>
            </md-table-row>

            <md-table-row
              v-for="(label, index) in ticketLabelService.list"
              :key="index"
            >
              <md-table-cell>{{ label.id }}</md-table-cell>
              <md-table-cell>
                {{ label.label_name }}
              </md-table-cell>
              <md-table-cell>
                <span
                  class="colored-box"
                  :style="{
                    backgroundColor:
                      ticketLabelService.colors[label.label_color],
                  }"
                ></span>
                {{ label.label_color }}
              </md-table-cell>
              <md-table-cell>
                <md-icon v-if="label.out_source === 0">cancel</md-icon>
                <md-icon v-else>check</md-icon>
              </md-table-cell>
            </md-table-row>
          </md-table>
        </md-card-content>
      </md-card>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { TicketLabelService } from "@/services/TicketLabelService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "CategoryManagement",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      ticketLabelService: new TicketLabelService(),
      newLabel: false,
      headers: [
        this.$tc("words.id"),
        this.$tc("words.name"),
        this.$tc("words.color"),
        this.$tc("words.outsourcing"),
      ],
      tableName: "Category",
      loading: false,
      subscriber: "ticket-labels",
    }
  },
  created() {
    this.getLabels()
  },
  methods: {
    async getLabels() {
      try {
        await this.ticketLabelService.getLabels()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.ticketLabelService.list.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveLabel() {
      let validator = await this.$validator.validateAll("")
      if (validator) {
        try {
          this.loading = true
          await this.ticketLabelService.createLabel(
            this.ticketLabelService.newLabelName,
            this.ticketLabelService.currentColor,
            this.ticketLabelService.outSourcing,
          )
          this.alertNotify("success", this.$tc("phrases.newCategory", 2))
          await this.getLabels()
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
        this.ticketLabelService.resetLabel()
        this.newLabel = false
      }
    },
  },
}
</script>

<style lang="scss">
.colored-box {
  width: 22px;
  height: 22px;
  margin: 0 5px 0 0;
  display: inline-block;
}
</style>
