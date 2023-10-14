<script setup lang="ts">
import Modal from "@/Components/Modal.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, reactive } from "vue";
import axios from "axios";

window.appChannel.bind("voted", function (data) {
  // debugger;
  let item = pageData.items.find((item) => item.id == data.feedback.id);
  item.upvotes += data.upvotes;
  item.downvotes += data.downvotes;
});
window.appChannel.bind("feedbackCreateOrUpdate", function (data) {
  // debugger;
  console.log(data);
  if (data.created) {
    if (pageData.searchTerm == "") {
      pageData.items.splice(0, 0, data.feedback);
    }
  } else {
    let item = pageData.items.find((item) => item.id == data.feedback.id);
    item.title = data.title;
    item.description += data.description;
  }
});
const apiMeta = ref({});
const loading = ref(false);
const pageData = reactive({
  feedback: null,
  modal: {
    show: false,
    feedback: null,
    feedbackIndex: null,
  },
  items: [],
  pageNo: 1,
  loading: false,
  searchTerm: "",
});
const search = () => {
  pageData.pageNo = 1;
  pageData.items = [];
  getPageData();
};
const getPageData = () => {
  return new Promise((resolve, reject) => {
    loading.value = true;
    fetch(`/api/feedback?page=${pageData.pageNo}&search=${pageData.searchTerm}`).then((response) =>
      response.json().then((response) => {
        console.log(response);
        const data = response.data;
        pageData.items = pageData.items.concat(data.data);
        delete data.items;
        apiMeta.value = data;
        loading.value = false;
        resolve(true);
      })
    );
  });
};
const infiniteScrollTrigger = ref();
// Add scroll event listener
const handleScroll = async () => {
  const trigger = infiniteScrollTrigger.value;
  if (trigger) {
    // debugger;
    const rect = trigger.getBoundingClientRect();

    const isInView = rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight);

    if (isInView) {
      await loadMoreItems();
    } else {
      console.log("not in view");
    }
  } else {
    console.log("trigger not visible");
  }
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
  getPageData().then(() => {
    handleScroll();
  });
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", handleScroll);
});

const loadMoreItems = () => {
  // Call your API or method to load more items
  // Update items.value with the new data
  if (apiMeta.value.next_page_url) {
    pageData.pageNo += 1;
    return getPageData();
  } else {
  }
};
const statusToText = {
  0: "Pending",
  1: "In Progress",
  2: "Completed",
};
const getStatusColorClass = (status) => {
  switch (status) {
    case 0:
      return "text-red-500"; // Tailwind CSS class for red text
    case 1:
      return "text-blue-500"; // Tailwind CSS class for blue text
    case 2:
      return "text-green-500"; // Tailwind CSS class for green text
    default:
      return "text-black"; // Default color if status is not recognized
  }
};
const createNewFeedback = (item) => {
  pageData.modal.feedback = {};
  pageData.modal.show = true;
};
const editFeedback = (item) => {
  pageData.modal.feedback = { ...item };
  pageData.modal.show = true;
};
const deleteFeedback = (item) => {
  const isConfirm = confirm("Are you sure you want to delete");
  if (isConfirm) {
    let config = {
      method: "delete",
      maxBodyLength: Infinity,
      url: `/api/feedback/${item.id}`,
    };
    pageData.loading = true;
    axios
      .request(config)
      .then((response) => {
        console.log(JSON.stringify(response.data));
        const index = pageData.items.findIndex((itm) => itm.id === item.id);
        pageData.items.splice(index, 1);
        alert("Feedback Deleted");
      })
      .catch((error) => {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
      });
  } else {
  }
};
const handleSubmit = () => {
  let feedback = pageData.modal.feedback;

  if (feedback.id) {
    // Means this is update request
    const id = feedback.id;
    delete feedback.id;
    const raw = JSON.stringify({ ...feedback });
    feedback.id = id;
    const config = {
      method: "put",
      url: `/api/feedback/${id}`,
      data: raw,
      headers: { "Content-Type": "application/json" },
    };
    pageData.loading = true;
    axios(config)
      .then(function (response) {
        const index = pageData.items.findIndex((item) => item.id === id);
        pageData.items[index] = { ...feedback };
      })
      .catch(function (error) {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
        alert("feedback editted successfully");
        pageData.modal.show = false;
      });
  } else {
    // Create New Item
    const raw = JSON.stringify({ ...feedback });
    const config = {
      method: "post",
      url: `/api/feedback`,
      data: raw,
      headers: { "Content-Type": "application/json" },
    };
    pageData.loading = true;
    axios(config)
      .then(function (response) {
        pageData.items = [];
        pageData.pageNo = 1;
        getPageData();
      })
      .catch(function (error) {
        console.log(error);
      })
      .finally(() => {
        pageData.loading = false;
        alert("Feedback Created successfully");
        pageData.modal.show = false;
      });
  }
};
import { usePage } from "@inertiajs/vue3";
const page = usePage();
console.log(page.props);

function vote(type = "upvote", feedback) {
  pageData.loading = true;
  fetch(`/api/feedback/${feedback.id}/${type}`, {
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
  })
    .then((response) =>
      response.json().then((res) => {
        if (response.status == 200) {
          feedback.upvotes = res.feedback.upvotes;
          feedback.downvotes = res.feedback.downvotes;
          feedback.vote_type = +(type == "upvote");
        } else {
          alert(res.message);
        }
      })
    )
    .catch((err) => console.log(err))
    .finally(() => {
      pageData.loading = false;
    });
}
</script>

<style scoped>
input {
  border: 1px solid #ccc;
  border-radius: 0;
}

button {
  border-radius: 0;
}

.infinite-scroll-trigger {
  height: 50px;
  /* Adjust this height as needed */
  background-color: #f5f5f5;
  /* Adjust the color as needed */
}

/* Add this CSS in your styles file or in your HTML file if you're not using an external stylesheet */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.loader {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #000;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  animation: spin 1s linear infinite;
}
</style>

<template>
  <Head title="Welcome" />
  <div
    class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white"
  >

    <div v-show="true" class="w-full sm:fixed sm:top-0 sm:right-0 p-6 text-right bg-gray-500">
      <Link
        v-if="$page.props.auth.user"
        :href="route('dashboard')"
        class="font-semibold text-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
        >Dashboard</Link
      >

      <template v-else>
        <Link
          :href="route('login')"
          class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
          >Log in</Link
        >

        <Link
          v-if="canRegister"
          :href="route('register')"
          class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
          >Register</Link
        >
      </template>
    </div>
    <div class="row p-12 mt-10 w-full">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <input v-model="pageData.searchTerm" type="text" class="border rounded-l py-2 px-3 focus:outline-none focus:ring focus:border-blue-300" placeholder="Search..." />
          <button @click="search" class="bg-blue-500 text-white py-2 px-4 rounded-r">Search</button>
        </div>
        <button @click="createNewFeedback" class="bg-green-500 text-white py-2 px-4 rounded">New Feedback</button>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Feedback Card 1 -->

        <div class="bg-white shadow p-4 rounded mb-4 hover:shadow-xl" v-for="feedback in pageData.items">
          <div class="flex justify-between content-between">
            <div class="flex flex-col justify-between">
              <div class="mb-2">
                <h2 class="text-xl font-bold text-gray-800">{{ feedback.title }}</h2>
                <p class="text-sm text-gray-600">{{ feedback.description }}</p>
              </div>
              <div class="row">
                <div class="flex items-center justify-self-stretch">
                  <p class="text-sm font-semibold">{{ feedback.category }}</p>
                  <p class="text-xs text-gray-400">{{ feedback.updated_at }}</p>
                </div>
                <div class="flex items-center justify-start mt-2">
                  <button class="bg-blue-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="editFeedback(feedback)">Edit</button>
                  <button class="bg-red-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="deleteFeedback(feedback)">Delete</button>
                  <Link class="bg-green-500 text-white px-4 py-2 rounded-b mr-2" :href="`/feedback/${feedback.id}`"> Open</Link>
                </div>
              </div>
            </div>
            <div class="w-1/6">
              <div class="flex flex-col items-end gap-3 pr-3 py-3">
                <button @click="vote('upvote', feedback)" :disabled="pageData.loading">
                  <svg
                    :class="{ 'text-green-600': !(feedback.vote_type == 1), 'text-gray-400': feedback.vote_type == 1 }"
                    class="w-6 h-6"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="5"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                  </svg>
                </button>
                <span class="p-1">{{ feedback.upvotes }}</span>
                <span class="p-1">{{ feedback.downvotes }}</span>
                <button @click="vote('downvote', feedback)" :disabled="pageData.loading">
                  <svg
                    :class="{ 'text-red-600': !(feedback.vote_type == 0), 'text-gray-400': feedback.vote_type == 0 }"
                    class="w-6 h-6"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="5"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Loading Row -->
      <div class="flex justify-center items-center mt-8 mb-8">
        <h2 v-if="apiMeta.current_page && !apiMeta.next_page_url">No More Items</h2>
        <div class="infinite-scroll-trigger" ref="infiniteScrollTrigger" v-else-if="!loading"></div>
        <div class="loader" v-else></div>
      </div>
    </div>

    <Modal :show="pageData.modal.show">
      <div class="row">
        <div class="flex justify-between items-center mb-4 mt-2 mr-2">
          <h1 class="pl-2" v-if="pageData.modal.feedback.id">Update Feedback</h1>
          <h1 class="pl-2" v-else>Create Feedback</h1>
          <span @click="pageData.modal.show = false" class="cursor-pointer"> X </span>
        </div>
      </div>
      <div class="row p-4">
        <form @submit.prevent="handleSubmit">
          <div class="mb-4">
            <label for="title" class="block text-gray-700 font-bold mb-2">Title:</label>
            <input v-model="pageData.modal.feedback.title" type="text" id="title" name="title" class="border rounded w-full py-2 px-3" required />
          </div>
          <div class="mb-4">
            <label for="description" class="block text-gray-700 font-bold mb-2">Description:</label>
            <textarea v-model="pageData.modal.feedback.description" id="description" name="description" class="border rounded w-full py-2 px-3" required></textarea>
          </div>
          <div class="mb-4">
            <label for="status" class="block text-gray-700 font-bold mb-2">Status:</label>
            <input type="text" v-model="pageData.modal.feedback.category" id="status" name="status" class="border rounded w-full py-2 px-3" required />
          </div>

          <div class="flex justify-center items-center mt-8 mb-8" v-if="pageData.loading">
            <div class="loader"></div>
          </div>
          <div class="flex justify-end" v-else>
            <button @click="handleSubmit" class="bg-blue-500 text-white px-4 py-2 rounded-full">Save</button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>
