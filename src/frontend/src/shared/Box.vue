<template>
  <md-card class="card-list-item-content">
    <div class="card-list-item-header">
      <div class="box" style="font-size: 1.5rem" v-bind:box-color="boxColor">
        <md-icon style="color: white">
          {{ boxIcon }}
        </md-icon>
        <slot></slot>
      </div>
    </div>
    <md-card-content>
      <div class="information" style="text-align: end">
        <div class="header-text" v-if="headerText" v-text="headerText"></div>

        <div class="sub-text" v-if="subText" v-html="subText"></div>

        <div
          class="additional-text"
          v-if="additionalText"
          v-html="additionalText"
        ></div>
      </div>
    </md-card-content>
  </md-card>
</template>

<script>
export default {
  name: "Box",
  props: {
    boxColor: {
      type: String,
      default: "default",
    },
    centerText: {
      type: Boolean,
      default: false,
    },
    headerText: String,
    subText: String,
    boxIcon: String,
    additionalText: String,
  },
}
</script>

<style>
.box {
  border-radius: 5px;
  padding: 30px;
  margin-top: 1vh;
  box-shadow:
    0 12px 20px -10px rgba(53, 53, 53, 0.3),
    0 4px 20px 0 rgba(0, 0, 0, 0.12),
    0 7px 8px -5px rgb(142, 142, 142);
}

.box[box-color="blue"] {
  background: linear-gradient(135deg, #26c6da 0%, #00acc1 100%);
}

.box[box-color="orange"] {
  background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
}

.box[box-color="red"] {
  background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
}

.box[box-color="green"] {
  background: linear-gradient(135deg, #6eaa44 0%, #578839 100%);
}

.header-text {
  color: rgb(148, 148, 148);
  margin-top: 0px;
  margin-bottom: 1rem;
}

.sub-text {
  color: #525252;
  font-weight: bold;
  font-size: 1.6rem;
}

.box-icon {
  font-size: 4rem;
}

.text-center {
  text-align: center;
}

.card-list {
  display: -webkit-inline-box !important;
  width: 100%;
}

.card-list-item {
  width: 100% !important;
}

.card-list-item-content {
  width: 100% !important;
}

.card-list-item-header {
  margin-top: -1.8rem;
  margin-left: 15px;
  float: left;
}

@media screen and (max-width: 479px) {
  .card-list-item-content {
    margin-bottom: 1rem !important;
    min-height: unset;
  }
}

@media screen and (min-width: 640px) {
  .card-list-item-content {
    min-height: 100% !important;
  }
}
</style>
