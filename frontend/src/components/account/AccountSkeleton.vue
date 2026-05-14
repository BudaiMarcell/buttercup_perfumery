<template>
  <!--
    Shared loading-state placeholder for every page in the My-Account
    section. Using one component (not page-tailored shapes) keeps the
    visual rhythm consistent — every account page fades the same way
    while loading, which reads as "polished" instead of "incomplete".
  -->
  <div class="account-skeleton">
    <Skeleton variant="title" width="45%" />
    <div v-for="n in rows" :key="n" class="account-skeleton-row">
      <Skeleton variant="line" width="35%" />
      <Skeleton variant="line" width="22%" style="margin-top: 8px;" />
      <Skeleton variant="line" width="100%" style="margin-top: 12px;" />
    </div>
  </div>
</template>

<script>
import Skeleton from '../Skeleton.vue';

export default {
  name: 'AccountSkeleton',
  components: { Skeleton },
  props: {
    // Number of placeholder "card" rows. Each page picks a count that
    // matches what the user typically sees once data lands — wishlist
    // tends to be wider, addresses tend to be shorter, etc.
    rows: { type: Number, default: 3 },
  },
};
</script>

<style scoped>
.account-skeleton {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 8px;
}
.account-skeleton-row {
  background: var(--white-warm, #fff);
  border: 1px solid rgba(156, 159, 136, 0.18);
  border-radius: 6px;
  padding: 16px 20px;
}
</style>
