<script setup lang="ts">
import Modal from "@/Components/Modal.vue";
import Layout from "@/Layouts/Layout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, reactive } from "vue";
import axios from "axios";
function isAnyFilterEnabled(search) {
  return search.showOnlyOwner || search.searchTerm || search.category;
}
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
    if (isAnyFilterEnabled(pageData.search)) {
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
  search: {
    showOnlyOwner: false,
    searchTerm: "",
    category: "",
  },
});
const search = () => {
  pageData.pageNo = 1;
  pageData.items = [];
  setTimeout(() => {
    getPageData();
  }, 200);
};
const getPageData = () => {
  if (!loading.value) {
    return new Promise((resolve, reject) => {
      loading.value = true;
      /*
    showOnlyOwner: false,
    searchTerm: "",
    category: "", */
      const queryParams = new URLSearchParams({ ...pageData.search, page: pageData.pageNo }).toString();
      fetch(`/api/feedback?${queryParams}`).then((response) =>
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
  }
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
#feedbackDetail {
  width: 100%;
}
.feedback-item {
  height: 100%;
}
</style>

<template>
  <Layout>
    <div class="row p-8 w-full">
      
      <div class="grid justify-start mb-4 grid-cols-2 lg:grid-cols-4 gap-3 items-center">
        <div>
          <input
            @keydown.enter="search"
            v-model="pageData.search.searchTerm"
            type="text"
            class="border rounded-l py-2 px-3 focus:outline-none focus:ring focus:border-blue-300"
            placeholder="Search..."
          />
          <button @click="search" class="bg-blue-500 text-white py-2 px-4 rounded-r md:ml-2">Search</button>
        </div>
        <div class="relative ml-2 md:ml-0 ">
          <label>Filter By Category: </label>
          <select
            placeholder="Filter By Category"
            class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
            v-model="pageData.search.category"
            @input.change="
              pageData.search.category = $event.target.value;
              search();
            "
          >
            <option class="bg-white hover:bg-gray-100" value=""></option>
            <option class="bg-white hover:bg-gray-100" :value="category.category" v-for="category in $page.props.categories">{{ category.category }}</option>
          </select>
        </div>
        <div class="">
          <label class="ml-3 mt-5 relative inline-flex items-center cursor-pointer">
            <input type="checkbox" @input.change="search" v-model="pageData.search.showOnlyOwner" class="sr-only peer" />
            <div
              class="w-11 h-6 mb-2 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"
            ></div>
            <span class="ml-3 mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Show only my Posts</span>
          </label>
        </div>
        <div class="text-right">
          <button @click="createNewFeedback" class="bg-green-500 text-white py-2 px-4 rounded">New Feedback</button>
        </div>
      </div>
      <h2 class="mb-2">Total items: {{ apiMeta.total }}</h2>

      <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Feedback Card 1 -->
        <div class="bg-white shadow p-4 rounded mb-4 hover:shadow-xl" v-for="feedback in pageData.items">
          <div class="flex feedback-item justify-between content-between">
            <div id="feedbackDetail" class="flex flex-col justify-between">
              <div class="mb-2">
                <h2 class="text-xl font-bold text-gray-800">{{ feedback.title }}</h2>
                <p class="text-sm text-gray-600">{{ feedback.description }}</p>
                <p class="text-sm text-gray-600">
                  Creator: <b>{{ feedback.creator_name }}</b>
                </p>
              </div>
              <div class="flex flex-col">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold">{{ feedback.category }}</p>
                  <p class="text-xs text-gray-400">{{ feedback.updated_at }}</p>
                </div>
                <div class="flex items-center justify-end mt-2">
                  <template v-if="$page.props.auth.user && feedback.user_id == $page.props.auth.user.id">
                    <button class="bg-blue-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="editFeedback(feedback)">Edit</button>
                    <button class="bg-red-500 text-white px-4 py-2 rounded-b mr-2" :disabled="pageData.loading" @click="deleteFeedback(feedback)">Delete</button>
                  </template>
                  <Link class="bg-green-500 text-white px-4 py-2 rounded-b mr-2" :href="`/feedback/${feedback.id}`"> Open</Link>
                </div>
              </div>
            </div>
            <div class="w-1/6">
              <div class="flex flex-col items-end gap-3 pr-3 py-3">
                <button v-show="$page.props.auth.user" @click="vote('upvote', feedback)" :disabled="pageData.loading || feedback.vote_type == 1">
                  <svg
                    :class="{ 'text-green-600': !(($page.props.auth.user) && feedback.vote_type == 1), 'text-gray-400': ($page.props.auth.user) && feedback.vote_type == 1 }"
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
                <span class="p-1 text-green-500">{{ feedback.upvotes }}</span>
                <span class="p-1 text-red-500">{{ feedback.downvotes }}</span>
                <button v-show="$page.props.auth.user"   @click="vote('downvote', feedback)" :disabled="pageData.loading || feedback.vote_type == 0">
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
        <div class="loader" v-if="loading"></div>
        <h2 v-else-if="apiMeta.current_page && !apiMeta.next_page_url">No More Items</h2>
        <div class="infinite-scroll-trigger" ref="infiniteScrollTrigger" v-else-if="!loading"></div>
      </div>
    </div>
  </Layout>
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
</template>
