<template>
  <div class="roadmap-container">
    <!-- Hero Section -->
    <div class="roadmap-hero">
      <div class="hero-content">
        <h1 class="hero-title">MPM Roadmap</h1>
        <p class="hero-tagline">
          Our vision for the future of MicroPowerManager — transparent,
          community-driven, and always evolving.
        </p>
        <div class="hero-actions">
          <a
            href="https://github.com/EnAccess/micropowermanager/issues"
            target="_blank"
            class="hero-btn hero-btn-primary"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <line x1="12" x2="12" y1="8" y2="12" />
              <line x1="12" x2="12.01" y1="16" y2="16" />
            </svg>
            Feature Requests
          </a>
          <a
            href="https://discord.osea-community.org/"
            target="_blank"
            class="hero-btn hero-btn-secondary"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path
                d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
              />
            </svg>
            Join Discord
          </a>
        </div>
      </div>
    </div>

    <!-- Release Navigation -->
    <div class="release-nav">
      <button
        :class="['release-nav-btn', { active: activeRelease === 'all' }]"
        @click="activeRelease = 'all'"
      >
        <span class="release-version">All Versions</span>
        <span class="release-target">Full Roadmap</span>
      </button>
      <button
        v-for="release in releases"
        :key="release.version"
        :class="[
          'release-nav-btn',
          { active: activeRelease === release.version },
        ]"
        @click="activeRelease = release.version"
      >
        <span class="release-version">{{ release.version }}</span>
        <span class="release-target">{{ release.target }}</span>
      </button>
    </div>

    <!-- Timeline Section -->
    <div class="timeline-section">
      <div
        v-for="release in filteredReleases"
        :key="release.version"
        class="release-block"
      >
        <div class="release-header">
          <div class="release-badge" :class="getReleaseStatus(release)">
            <span class="badge-dot"></span>
            {{ getReleaseStatusLabel(release) }}
          </div>
          <h2 class="release-title">{{ release.version }}</h2>
          <p class="release-subtitle">{{ release.title }}</p>
          <p class="release-target-date">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
              <line x1="16" x2="16" y1="2" y2="6" />
              <line x1="8" x2="8" y1="2" y2="6" />
              <line x1="3" x2="21" y1="10" y2="10" />
            </svg>
            Target: {{ release.target }}
          </p>
          <p class="release-description">{{ release.description }}</p>
        </div>

        <!-- Milestones Grid -->
        <div class="milestones-grid">
          <div
            v-for="milestone in release.milestones"
            :key="milestone.id"
            class="milestone-card"
            :class="milestone.status"
          >
            <div class="milestone-header">
              <span class="milestone-status-badge" :class="milestone.status">
                {{ getStatusLabel(milestone.status) }}
              </span>
              <span v-if="milestone.category" class="milestone-category">
                {{ milestone.category }}
              </span>
            </div>

            <h3 class="milestone-title">{{ milestone.title }}</h3>
            <p class="milestone-description">{{ milestone.description }}</p>

            <!-- Sub-features -->
            <ul v-if="milestone.features?.length" class="milestone-features">
              <li
                v-for="feature in milestone.features"
                :key="feature.title"
                class="feature-item"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <polyline points="9 11 12 14 22 4" />
                  <path
                    d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                  />
                </svg>
                <span>{{ feature.title }}</span>
              </li>
            </ul>

            <!-- GitHub Links -->
            <div v-if="milestone.github" class="milestone-links">
              <a
                v-if="milestone.github.issue"
                :href="`https://github.com/EnAccess/micropowermanager/issues/${milestone.github.issue}`"
                target="_blank"
                class="github-link"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="12" cy="12" r="10" />
                  <line x1="12" x2="12" y1="8" y2="12" />
                  <line x1="12" x2="12.01" y1="16" y2="16" />
                </svg>
                Issue #{{ milestone.github.issue }}
              </a>
              <a
                v-if="milestone.github.pr"
                :href="`https://github.com/EnAccess/micropowermanager/pull/${milestone.github.pr}`"
                target="_blank"
                class="github-link"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="18" cy="18" r="3" />
                  <circle cx="6" cy="6" r="3" />
                  <path d="M13 6h3a2 2 0 0 1 2 2v7" />
                  <line x1="6" x2="6" y1="9" y2="21" />
                </svg>
                PR #{{ milestone.github.pr }}
              </a>
              <a
                v-if="milestone.github.commit"
                :href="`https://github.com/EnAccess/micropowermanager/commit/${milestone.github.commit}`"
                target="_blank"
                class="github-link"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="12" cy="12" r="4" />
                  <line x1="1.05" x2="7" y1="12" y2="12" />
                  <line x1="17.01" x2="22.96" y1="12" y2="12" />
                </svg>
                {{ milestone.github.commit.substring(0, 7) }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Future Vision Section -->
    <div class="future-section">
      <h2 class="future-title">Looking Ahead</h2>
      <div class="future-grid">
        <div class="future-card">
          <div class="future-icon">🔧</div>
          <h3>Developer Experience</h3>
          <p>Better documentation, SDKs, and APIs for seamless integrations</p>
        </div>
        <div class="future-card">
          <div class="future-icon">🔗</div>
          <h3>Data Interoperability</h3>
          <p>Industry-standard data formats and exchange protocols</p>
        </div>
        <div class="future-card">
          <div class="future-icon">☁️</div>
          <h3>Cloud-Native</h3>
          <p>Scalable multi-tenant deployments for growing operations</p>
        </div>
        <div class="future-card">
          <div class="future-icon">🧩</div>
          <h3>Modular Architecture</h3>
          <p>Flexible, plugin-based energy management solutions</p>
        </div>
      </div>
    </div>

    <!-- Community Section -->
    <div class="community-section">
      <div class="community-content">
        <h2>Shape the Future of MPM</h2>
        <p>
          MPM is built by and for the community. Your feedback drives our
          priorities. Join us in building tools that empower sustainable energy
          access worldwide.
        </p>
        <div class="community-actions">
          <a
            href="https://discord.osea-community.org/"
            target="_blank"
            class="community-btn"
          >
            Join Discord Community
          </a>
          <a
            href="https://github.com/EnAccess/micropowermanager"
            target="_blank"
            class="community-btn community-btn-outline"
          >
            Contribute on GitHub
          </a>
        </div>
      </div>
    </div>

    <p class="last-updated">Last updated: {{ lastUpdated }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue"
import { useData } from "vitepress"

const { frontmatter } = useData()

interface GitHubLinks {
  issue?: number
  pr?: number
  commit?: string
}

interface Feature {
  title: string
  completed?: boolean
}

interface Milestone {
  id: string
  title: string
  description: string
  status: "completed" | "in-progress" | "specified" | "planned" | "exploring"
  category?: string
  features?: Feature[]
  github?: GitHubLinks
}

interface Release {
  version: string
  title: string
  target: string
  description: string
  milestones: Milestone[]
}

const releases = computed<Release[]>(() => frontmatter.value.releases || [])
const lastUpdated = computed(
  () => frontmatter.value.lastUpdated || "January 2026",
)

const activeRelease = ref<string | "all">("all")

const filteredReleases = computed(() => {
  if (activeRelease.value === "all") return releases.value
  return releases.value.filter((r) => r.version === activeRelease.value)
})

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    completed: "Completed",
    "in-progress": "In Progress",
    specified: "Specified",
    planned: "Planned",
    exploring: "Exploring",
  }
  return labels[status] || status
}

const getReleaseStatus = (release: Release): string => {
  const statuses = release.milestones.map((m) => m.status)
  if (statuses.every((s) => s === "completed")) return "completed"
  if (statuses.some((s) => s === "in-progress")) return "in-progress"
  return "planned"
}

const getReleaseStatusLabel = (release: Release): string => {
  const status = getReleaseStatus(release)
  const labels: Record<string, string> = {
    completed: "Released",
    "in-progress": "In Development",
    planned: "Upcoming",
  }
  return labels[status] || "Upcoming"
}
</script>

<style scoped lang="scss">
$pc: 1440px;
$laptop: 1280px;
$pad: 959px;
$tablet: 719px;
$mobile: 419px;

.roadmap-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* Hero Section */
.roadmap-hero {
  position: relative;
  padding: 4rem 0;
  text-align: center;
  background: linear-gradient(
    135deg,
    rgba(27, 117, 186, 0.08) 0%,
    rgba(119, 217, 247, 0.08) 50%,
    rgba(23, 69, 105, 0.08) 100%
  );
  border-radius: 1.5rem;
  margin: 2rem 0;
  overflow: hidden;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
      radial-gradient(
        circle at 20% 80%,
        rgba(27, 117, 186, 0.1) 0%,
        transparent 50%
      ),
      radial-gradient(
        circle at 80% 20%,
        rgba(119, 217, 247, 0.1) 0%,
        transparent 50%
      );
    pointer-events: none;
  }
}

.hero-content {
  position: relative;
  z-index: 1;
}

.hero-title {
  font-size: 3rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--vp-c-brand-1), var(--vp-c-brand-4));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1rem;
  letter-spacing: -0.02em;
  line-height: 1.2;
  padding-bottom: 0.1em;

  @media (max-width: $tablet) {
    font-size: 2.25rem;
  }
}

.hero-tagline {
  font-size: 1.25rem;
  color: var(--vp-c-text-2);
  max-width: 600px;
  margin: 0 auto 2rem;
  line-height: 1.6;

  @media (max-width: $tablet) {
    font-size: 1rem;
    padding: 0 1rem;
  }
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  transition: all 0.2s ease;

  &-primary {
    background: var(--vp-c-brand-1);
    color: white;

    &:hover {
      background: var(--vp-c-brand-2);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(27, 117, 186, 0.3);
    }
  }

  &-secondary {
    background: var(--vp-c-bg-soft);
    color: var(--vp-c-text-1);
    border: 1px solid var(--vp-c-divider);

    &:hover {
      background: var(--vp-c-bg-alt);
      border-color: var(--vp-c-brand-1);
      color: var(--vp-c-brand-1);
    }
  }
}

/* Release Navigation */
.release-nav {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
  margin: 2rem 0;
  padding: 1rem;
  background: var(--vp-c-bg-soft);
  border-radius: 1rem;
}

.release-nav-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.75rem 1.5rem;
  border: 2px solid transparent;
  border-radius: 0.75rem;
  background: var(--vp-c-bg);
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    border-color: var(--vp-c-brand-1);
  }

  &.active {
    border-color: var(--vp-c-brand-1);
    background: rgba(27, 117, 186, 0.1);
  }

  .release-version {
    font-weight: 700;
    font-size: 1rem;
    color: var(--vp-c-text-1);
  }

  .release-target {
    font-size: 0.8rem;
    color: var(--vp-c-text-3);
    margin-top: 0.25rem;
  }
}

/* Timeline Section */
.timeline-section {
  margin: 3rem 0;
}

.release-block {
  margin-bottom: 4rem;
  padding-bottom: 4rem;
  border-bottom: 1px solid var(--vp-c-divider);

  &:last-child {
    border-bottom: none;
  }
}

.release-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.release-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 1rem;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 1rem;

  .badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  &.completed {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
    .badge-dot {
      background: #16a34a;
    }
  }

  &.in-progress {
    background: rgba(27, 117, 186, 0.15);
    color: var(--vp-c-brand-1);
    .badge-dot {
      background: var(--vp-c-brand-1);
    }
  }

  &.planned {
    background: rgba(156, 163, 175, 0.15);
    color: #6b7280;
    .badge-dot {
      background: #6b7280;
      animation: none;
    }
  }
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.release-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--vp-c-text-1);
  margin: 0.5rem 0;
}

.release-subtitle {
  font-size: 1.25rem;
  color: var(--vp-c-brand-1);
  font-weight: 600;
  margin: 0.5rem 0;
}

.release-target-date {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--vp-c-text-2);
  margin: 0.5rem 0;
}

.release-description {
  max-width: 700px;
  margin: 1rem auto 0;
  color: var(--vp-c-text-2);
  line-height: 1.6;
}

/* Milestones Grid */
.milestones-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;

  @media (max-width: $mobile) {
    grid-template-columns: 1fr;
  }
}

.milestone-card {
  position: relative;
  padding: 1.5rem;
  background: var(--vp-c-bg);
  border: 1px solid var(--vp-c-divider);
  border-radius: 1rem;
  transition: all 0.2s ease;
  overflow: hidden;

  &::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
  }

  &.completed::before {
    background: #16a34a;
  }
  &.in-progress::before {
    background: var(--vp-c-brand-1);
  }
  &.specified::before {
    background: #8b5cf6;
  }
  &.planned::before {
    background: #f59e0b;
  }
  &.exploring::before {
    background: #6b7280;
  }

  &:hover {
    border-color: var(--vp-c-brand-1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
  }
}

.milestone-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.milestone-status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;

  &.completed {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
  }
  &.in-progress {
    background: rgba(27, 117, 186, 0.15);
    color: var(--vp-c-brand-1);
  }
  &.specified {
    background: rgba(139, 92, 246, 0.15);
    color: #8b5cf6;
  }
  &.planned {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
  }
  &.exploring {
    background: rgba(107, 114, 128, 0.15);
    color: #6b7280;
  }
}

.milestone-category {
  padding: 0.25rem 0.75rem;
  background: var(--vp-c-bg-soft);
  border-radius: 1rem;
  font-size: 0.7rem;
  color: var(--vp-c-text-2);
}

.milestone-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--vp-c-text-1);
  margin: 0 0 0.5rem;
  line-height: 1.4;
}

.milestone-description {
  font-size: 0.9rem;
  color: var(--vp-c-text-2);
  line-height: 1.6;
  margin: 0 0 1rem;
}

.milestone-features {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--vp-c-text-2);
  padding: 0.25rem 0;

  svg {
    flex-shrink: 0;
    margin-top: 2px;
    color: var(--vp-c-brand-1);
  }
}

.milestone-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid var(--vp-c-divider);
}

.github-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: var(--vp-c-bg-soft);
  border-radius: 0.5rem;
  font-size: 0.8rem;
  color: var(--vp-c-text-2);
  text-decoration: none;
  transition: all 0.2s ease;

  &:hover {
    background: var(--vp-c-brand-1);
    color: white;
  }

  svg {
    flex-shrink: 0;
  }
}

/* Future Section */
.future-section {
  padding: 3rem 0;
  text-align: center;
}

.future-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--vp-c-text-1);
  margin-bottom: 2rem;
}

.future-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.future-card {
  padding: 2rem;
  background: var(--vp-c-bg-soft);
  border-radius: 1rem;
  text-align: center;
  transition: all 0.2s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  }

  .future-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
  }

  h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--vp-c-text-1);
    margin: 0 0 0.5rem;
  }

  p {
    font-size: 0.9rem;
    color: var(--vp-c-text-2);
    margin: 0;
    line-height: 1.5;
  }
}

/* Community Section */
.community-section {
  margin: 4rem 0;
  padding: 4rem 2rem;
  background: linear-gradient(
    135deg,
    var(--vp-c-brand-1) 0%,
    var(--vp-c-brand-2) 100%
  );
  border-radius: 1.5rem;
  text-align: center;
}

.community-content {
  max-width: 600px;
  margin: 0 auto;

  h2 {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0 0 1rem;
  }

  p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    margin: 0 0 2rem;
  }
}

.community-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.community-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.75rem;
  border-radius: 0.75rem;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: all 0.2s ease;
  background: white;
  color: var(--vp-c-brand-1);

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  }

  &-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.5);

    &:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: white;
    }
  }
}

.last-updated {
  text-align: center;
  font-size: 0.85rem;
  color: var(--vp-c-text-3);
  margin: 2rem 0;
  font-style: italic;
}
</style>
